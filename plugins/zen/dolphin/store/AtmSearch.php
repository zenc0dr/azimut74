<?php namespace Zen\Dolphin\Store;

use Zen\Dolphin\Models\Hotel;
use Zen\Grinder\Classes\Grinder;
use Zen\Dolphin\Classes\Core;
use Zen\Dolphin\Models\City;
use Zen\Dolphin\Models\Tour;
use Zen\Dolphin\Models\Place;
use Carbon\Carbon;
use DB;
use BackendAuth;

class AtmSearch extends Core
{
    private $atm_db = null; # Поисковая база


    private $hotel_types = []; // hotel_id => hotel_type_name
    private $tour_names = []; // tour_id => tour_name
    private $tarrif_names = [];
    private $number_names = []; // tarrif_id => number_name

    public function allowedDates()
    {
        DB::unprepared("SET sql_mode = ''");
        return DB::table('zen_dolphin_tours as tour')
            ->whereNotNull('tour.waybill')
            ->where('tour.type_id', 1)
            ->join(
                'zen_dolphin_prices as price',
                'price.tour_id', '=', 'tour.id'
            )
            ->groupBy('price.date')
            ->select(
                'price.date as date'
            )
            ->pluck('date')
            ->map(function ($item) {
                return $this->dateFromMysql($item);
            }) # Преобразование формата дат
            ->toArray();
    }

    private $debug_cell;

    public function atmDb($dates, $geo_objects, $query)
    {

        if (!$dates) return;
        if (!$geo_objects) return;

        # \Log::debug($query);

        # Преобразование дат в формат mysql
        foreach ($dates as &$date) {
            $date = $this->dateToMysql($date);
        }

        $geo_childrens = $this->addGeoChildrens($geo_objects); # TODO: Не возвращает места ??

        //dd($geo_childrens);


        DB::unprepared("SET sql_mode = ''");
        $pivot = DB::table('zen_dolphin_prices as price')
            ->whereIn('price.date', $dates)
            ->whereNotNull('tour.waybill')
            ->where('tour.type_id', 1)

            # Гео объекты
            ->where(function ($query) use ($geo_objects, $geo_childrens) {
                foreach ($geo_objects as $geo_object) {
                    $query->orWhere('tour.waybill', 'like', "% $geo_object %");
                }

                # Дочерние гео-объекты
                if ($geo_childrens) {
                    foreach ($geo_childrens as $geo_object) {
                        $query->orWhere('tour.waybill', 'like', "%$geo_object%");
                    }
                }
            })
            ->join('zen_dolphin_tours as tour', 'tour.id', '=', 'price.tour_id')
            ->join('zen_dolphin_tarrifs as tarrif', 'tarrif.tour_id', '=', 'tour.id')
            ->join('zen_dolphin_hotels as hotel', 'hotel.id', '=', 'tarrif.hotel_id')
            ->join('zen_dolphin_azcomforts as comfort', 'comfort.id', '=', 'tarrif.azcomfort_id')
            ->join('zen_dolphin_azpansions as pansion', 'pansion.id', '=', 'tarrif.azpansion_id')
            ->leftJoin('zen_dolphin_hotel_types as hotel_type', 'hotel_type.id', '=', 'hotel.type_id')
            ->select(
                'tour.id as tour_id',
                'tour.name as tour_name',
                'tour.duration as days',

                'comfort.id as comfort_id',
                'comfort.name as comfort_name',

                'pansion.id as pansion_id',
                'pansion.name as pansion_name',

                'tarrif.id as tarrif_id',
                'tarrif.name as tarrif_name',
                'tarrif.number_name as number_name',

                'hotel.id as hotel_id',
                'hotel.name as hotel_name',
                'hotel.to_sea as to_sea',
                'hotel_type.id as hotel_type_id',
                'hotel_type.name as hotel_type_name'
            )
            ->get();


        $atm_db = [
            'tours' => [],
            'comforts' => [],
            'pansions' => [],
            'hotels' => [],
        ];

        foreach ($pivot as $record) {


            $cell = [
                $record->tour_id, # 0
                $record->comfort_id, # 1
                $record->pansion_id, # 2
                $record->tarrif_id, # 3
                $record->hotel_id, # 4
                $record->to_sea, # 5
                $record->days, # 6
            ];

            $key = join('.', $cell);

            $atm_db['tours'][$key] = $record->tour_name;

            if (!isset($atm_db['comforts'][$record->comfort_id])) {
                $atm_db['comforts'][$record->comfort_id] = $record->comfort_name;
            }

            if (!isset($atm_db['pansions'][$record->pansion_id])) {
                $atm_db['pansions'][$record->pansion_id] = $record->pansion_name;
            }

            if (!isset($atm_db['hotels'][$record->hotel_id])) {

                $atm_db['hotels'][$record->hotel_id] = [
                    'name' => $record->hotel_name,
                    'to_sea' => $record->to_sea
                ];

                $this->hotel_types[$record->hotel_id] = $record->hotel_type_name;
            }

            if (!isset($this->tour_names[$record->tour_id])) {
                $this->tour_names[$record->tour_id] = $record->tour_name;
            }

            if (!isset($this->number_names[$record->tarrif_id])) {
                $this->number_names[$record->tarrif_id] = $record->number_name;
                $this->tarrif_names[$record->tarrif_id] = $record->tarrif_name;
            }

        }

        $atm_db['tours'] = array_keys($atm_db['tours']);

        ksort($atm_db['comforts']);
        ksort($atm_db['pansions']);
        ksort($atm_db['hotels']);

        $allowed_services = $this->addServices($atm_db['hotels']);

        $atm_db['services'] = $allowed_services ?? [];

        # Формирование сырой atm_db
        $this->atm_db = $atm_db;

        # 0.1778 сек

        # Облагораживание
        $this->restructurize(); # 0.1798 сек
        $this->hotelsRestructurize(); # 0.1942 сек.
        $this->renderTours($query, $dates); # 6.0320 сек.
        $this->servicesCheck();
        $this->additionalData();
        $this->addOperators();

        return $this->atm_db;
    }

    private function addOperators()
    {
        if (!BackendAuth::check()) return;
        $tarrifs_ids = collect($this->atm_db['tours'])->pluck('tarrif_id')->unique()->toArray();
        $tarrifs_pivot = DB::table('zen_dolphin_tarrifs as tarrif')
            ->whereIn('tarrif.id', $tarrifs_ids)
            ->join('zen_dolphin_operators as operator', 'operator.id', '=', 'tarrif.operator_id')
            ->select(
                'tarrif.id as tarrif_id',
                'operator.name as operator_name'
            )->get();

        $arr = [];
        foreach ($tarrifs_pivot as $item) {
            $arr[$item->tarrif_id] = $item->operator_name;
        }

        foreach ($this->atm_db['tours'] as &$tour) {
            $tour['op'] = $arr[$tour['tarrif_id']];
        }
    }

    private function additionalData()
    {
        $hotels_ids = array_keys($this->atm_db['hotels']);

        $hotels = Hotel::whereIn('id', $hotels_ids)->get();

        $hotels_data = [];
        foreach ($hotels as $hotel) {
            $hotels_data[$hotel->id] = [
                'image' => $hotel->one_pic,
                'gps' => $hotel->gps,
                'geo_object' => @$hotel->geo_object->name
                //'address' => $hotel->address, # TODO: Не известно
            ];
        }

        //dd($hotels_data); // 2373 Костакис

        foreach ($this->atm_db['tours'] as &$tour) {
            $hotel_data = $hotels_data[$tour['image']];
            $tour['image'] = $hotel_data['image'];
            $tour['gps'] = $hotel_data['gps'];
            $tour['geo_object'] = $hotel_data['geo_object'];
        }
    }

    private function servicesCheck()
    {
        $dervice_ids = [];
        foreach ($this->atm_db['tours'] as $tour) {
            $stamp = explode('.', $tour['stamp']);
            $services = @$stamp[7];
            if ($services) {
                $services = explode('-', $services);
                foreach ($services as $service_id) {
                    $dervice_ids[$service_id] = null;
                }
            }
        }
        $dervice_ids = array_keys($dervice_ids);
        $real_services = [];
        foreach ($dervice_ids as $dervice_id) {
            if (isset($this->atm_db['services'][$dervice_id])) {
                $real_services[$dervice_id] = $this->atm_db['services'][$dervice_id];
            }
        }

        $this->atm_db['services'] = $real_services;
    }

    private $matrix; # Комбинаторная матрица PAC12345

    private function renderTours($query, $dates)
    {
        $this->matrix = collect(file(base_path('plugins/zen/dolphin/storage/pac')))
            ->map(function ($item) {
                return trim($item);
            })->toArray();

        $adults = intval($query['adults']);
        $children_ages = $query['childrens'] ?? [];

        foreach ($children_ages as &$age) {
            $age = intval($age);
        }

        $results = [];

        $prices_pivot = $this->makePricesPivot($this->atm_db['tours'], $dates);

        foreach ($this->atm_db['tours'] as $stamp) {

            //$this->debug_cell = $stamp;

            $tour_keys = explode('.', $stamp);
            $tour_id = $tour_keys[0];
            $tarrif_id = $tour_keys[3];

            //$prices = $this->getPrices($tour_id, $tarrif_id, $dates); # 0.02 сек.
            $prices = @$prices_pivot["$tour_id:$tarrif_id"];
            if (!$prices) continue;

            $results = array_merge($results, $this->makeResults($stamp, $tour_keys, $prices, $adults, $children_ages));
        }

        $filtred_tours = [];
        foreach ($this->atm_db['tours'] as $tour) {
            if (!isset($results[$tour])) continue;
            foreach ($results[$tour] as $result_tour) {
                $result_tour['stamp'] = $tour;
                $filtred_tours[] = $result_tour;
            }
        }

        $this->atm_db['tours'] = $filtred_tours;

        //$this->atm_db['tours'] = collect($this->atm_db['tours'])->sortBy('sum')->toArray();
        $this->atm_db['tours'] = array_values($this->atm_db['tours']);
    }

    private function makePricesPivot($stamps, $dates)
    {
        $tour_ids = [];
        $tarrif_ids = [];
        foreach ($stamps as $stamp) {
            $stamp = explode('.', $stamp);
            $tour_ids[] = $stamp[0];
            $tarrif_ids[] = $stamp[3];
        }

        $tour_ids = array_unique($tour_ids);
        $tarrif_ids = array_unique($tarrif_ids);

        $prices = DB::table('zen_dolphin_prices as price')
            ->whereIn('price.date', $dates)
            ->whereIn('price.tour_id', $tour_ids)
            ->whereIn('price.tarrif_id', $tarrif_ids)
            ->join('zen_dolphin_pricetypes as pricetype', 'pricetype.id', '=', 'price.pricetype_id')
            ->join('zen_dolphin_azrooms as azroom', 'azroom.id', '=', 'price.azroom_id')
            ->select(
                'price.tarrif_id as tarrif_id',
                'price.tour_id as tour_id',
                'price.date as date',
                'pricetype.id as pricetype_id',
                'pricetype.name as pricetype_name',
                'azroom.id as places',
                'price.price as price',
                'price.age_min as age_min',
                'price.age_max as age_max'
            )
            ->get();

        if (!$prices) return;

        $test = [];
        foreach ($prices as $price) {
            if ($price->tour_id == 4) {
                $test[] = $price;
            }
        }

        $prices_pivot = [];
        foreach ($prices as $price) {
            $prices_pivot["{$price->tour_id}:{$price->tarrif_id}"][] = $price;
        }
        return $prices_pivot;
    }

    private function restructurize()
    {
        foreach ($this->atm_db['tours'] as &$tour_key) {
            $key_arr = explode('.', $tour_key);
            $hotel_id = $key_arr[4];
            @$services = $this->atm_db['hotels'][$hotel_id]['services'];
            if (!$services) continue;
            $tour_key .= '.' . join('-', $services);
        }
    }

    private function hotelsRestructurize()
    {
        foreach ($this->atm_db['hotels'] as $key => &$hotel) {
            $hotel = $hotel['name'];
        }
    }

    # Функция добавить дочерние элементы для гео-объектов
    private function addGeoChildrens($geo_objects)
    {
        //dd($geo_objects);
        $geo_objects = collect($geo_objects)->filter(function ($item) {
            # Отбрасываем метки мест (Потому что у мест уже нет детей)
            if (strpos($item, 'ps') !== false) return false;

            # Отбрасываем метки стран ибо запрещено по нима искать
            if (strpos($item, '0:') !== false) return false;

            return true;
        })->toArray();

        if (!$geo_objects) return;

        # Получить гео.объекты, родителями которых являются эти
        # regions - Тут только регионы

        $regions_ids = collect($geo_objects)->map(function ($item) {
            return explode(':', $item)[1];
        })->toArray();

        $city_geocodes = City::whereIn('region_id', $regions_ids)
            //->where('pertain_id', 0)
            ->get()
            ->map(function ($item) {
                return "2:{$item->id}";
            })->toArray();

        if ($city_geocodes) {
            $geo_objects = array_merge($geo_objects, $city_geocodes);
        }

        $places_geocodes = Place::whereIn('geo_code', $geo_objects)
            ->pluck('id')->map(function ($place_id) {
                return "3:ps$place_id";
            })->toArray();

        if (!$city_geocodes) $city_geocodes = [];
        if (!$places_geocodes) $places_geocodes = [];

        $output = array_merge($city_geocodes, $places_geocodes);

        return $output;
    }

    private function addServices(&$hotels)
    {
        # Наличие кафе
        # Наличие кухни
        # Наличие бассейна

        $hotel_ids = array_keys($hotels);

        $pivot = DB::table('zen_dolphin_hstructures_pivot as pivot')
            ->whereIn('pivot.hotel_id', $hotel_ids)
            ->join('zen_dolphin_hstructures as service', 'service.id', '=', 'pivot.structure_id')
            ->select(
                'pivot.hotel_id as hotel_id',
                'service.id as service_id',
                'service.name as service_name'
            )
            ->get();

        $services = [
            1 => [
                'name' => 'Наличие кафе',
                'entry' => 'кафе',
            ],
            2 => [
                'name' => 'Наличие кухни',
                'entry' => 'кухн'
            ],
            3 => [
                'name' => 'Наличие бассейна',
                'entry' => 'бассе'
            ]
        ];

        $allowed_services_ids = [];

        foreach ($pivot as $record) {
            $service_name = $record->service_name;
            foreach ($services as $service_id => $service) {
                if (strpos(mb_strtolower($service_name), $service['entry']) !== false) {
                    if (!isset($hotels[$record->hotel_id]['services'])) {
                        $hotels[$record->hotel_id]['services'][] = $service_id;
                        $allowed_services_ids[$service_id] = null;
                    }

                    if (!in_array($service_id, $hotels[$record->hotel_id]['services'])) {
                        $hotels[$record->hotel_id]['services'][] = $service_id;
                        $allowed_services_ids[$service_id] = null;
                    }
                }
            }
        }

        $allowed_services_ids = array_keys($allowed_services_ids);

        $allowed_services = [];
        foreach ($services as $service_id => $service) {
            if (in_array($service_id, $allowed_services_ids)) {
                $allowed_services[$service_id] = $service['name'];
            }
        }

        return $allowed_services;
    }

    private function addImages(&$results, $tours_ids)
    {
        $tours = Tour::whereIn('id', $tours_ids)->get();

        $images = [];
        foreach ($tours as $tour) {
            $images[$tour->id] = ($tour->preview_image)
                ? Grinder::open($tour->preview_image)->options('w500')->getThumb()
                : '/plugins/zen/dolphin/assets/images/tour-no-image.jpg';
        }

        foreach ($results as &$result) {
            $result['image'] = $images[$result['tour_id']];
        }
    }

    private $datesMemory = [];

    private function formatDate($date, $days)
    {
        if (isset($this->datesMemory["$date:$days"])) return $this->datesMemory["$date:$days"];

        $dow = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
        $date_1 = Carbon::parse($date);
        $timestamp = $date_1->timestamp;
        $date_1_dow = $dow[$date_1->dayOfWeek];
        $date_1_formated = $date_1->format('d.m.Y');

        $date_2 = $date_1->addDays($days - 1);
        $date_2_dow = $dow[$date_2->dayOfWeek];
        $date_2 = $date_2->format('d.m.Y');

        $output = [
            'arr' => [
                'd1' => $date_1_formated,
                'd1d' => $date_1_dow,
                'd2' => $date_2,
                'd2d' => $date_2_dow
            ],
            'string' => "$date_1_formated <b>($date_1_dow)</b> - $date_2 <b>($date_2_dow)</b>",
            'timestamp' => $timestamp
        ];

        $this->datesMemory["$date:$days"] = $output;

        return $output;
    }

    # Варианты цен для тарифа тура
    private function getPrices($tour_id, $tariff_id, $dates)
    {
        $prices = DB::table('zen_dolphin_prices as price')
            ->whereIn('price.date', $dates)
            ->where('price.tour_id', $tour_id)
            ->where('price.tarrif_id', $tariff_id)
            ->join('zen_dolphin_pricetypes as pricetype', 'pricetype.id', '=', 'price.pricetype_id')
            ->join('zen_dolphin_azrooms as azroom', 'azroom.id', '=', 'price.azroom_id')
            ->select(
                'price.tarrif_id as tarrif_id',
                'price.tour_id as tour_id',
                'price.date as date',
                'pricetype.id as pricetype_id',
                'pricetype.name as pricetype_name',
                'azroom.id as places',
                'price.price as price',
                'price.age_min as age_min',
                'price.age_max as age_max'
            )
            ->get();

        return $prices;
    }

    private function makeResults($stamp, $record, $prices, $adults, $children_ages)
    {
        $childrens_count = count($children_ages);
        $pioples_count = $adults + $childrens_count;
        $ac = "$adults$childrens_count";

        $dates = collect($prices)->pluck('date')->unique()->toArray();

        $price_packs = [];

        foreach ($dates as $date) {
            foreach ($prices as $price_record) {
                if ($price_record->date != $date) continue;
                $price_packs[$price_record->date][] = $price_record;
            }
        }

        $results = [];

        foreach ($price_packs as $date => $price_pack) {

            $place_packs = [];

            # Допускаются только варианты мест 3 человека = 3 осн, 3 осн + 1 доп, 3 осн + 1 без мест, , 3 осн + 1 без мест+ 1 доп
            foreach ($price_pack as $price_pack_record) {
                if ($price_pack_record->places <= $pioples_count + 2) {
                    $place_packs[$price_pack_record->places][] = $price_pack_record;
                }
            }

            $place_packs[$price_pack_record->places][] = $price_pack_record;

            foreach ($place_packs as $place_count => $place_pack) {

                $matrix_key = "$place_count$ac";

                $combinations = $this->getCombinations($matrix_key);

                foreach ($combinations as $combination) {
                    $priceline = $this->handleCombination($combination, $place_pack, $children_ages);
                    if ($priceline) {

                        $days = intval($record[6]);

                        $date_arr = $this->formatDate($date, $days);
                        $price_arr = $this->formatPriceLine($priceline);

                        if (!$price_arr['sum']) continue;

                        $tour_id = intval($record[0]);
                        $pansion_id = intval($record[2]);
                        $tarrif_id = intval($record[3]);
                        $tarrif_name = $this->tarrif_names[$tarrif_id];
                        $hotel_id = intval($record[4]);
                        $to_sea = intval($record[5]);

                        $hotel_type = $this->hotel_types[$hotel_id];

                        if ($hotel_type) {
                            $hotel_type = str_replace('Гостиница', 'гостиницу', $hotel_type);
                            $hotel_type = str_replace('гостиница', 'гостиницу', $hotel_type);
                            $hotel_type = " $hotel_type";
                        }

                        $hotel_name = $this->atm_db['hotels'][$hotel_id];
                        $tour_name = $this->tour_names[$tour_id];
                        $tour_name = "Автобусный $tour_name в$hotel_type \"$hotel_name\"";
                        $number_name = $this->number_names[$tarrif_id];
                        $pansion_name = $this->atm_db['pansions'][$pansion_id];

                        $result = [
                            'tour_id' => $tour_id,
                            'tour_name' => $tour_name,
                            'hotel_id' => $hotel_id,
                            'hotel_name' => $hotel_name,
                            'image' => $hotel_id,
                            'tarrif_id' => $tarrif_id,
                            'tarrif_name' => $tarrif_name,
                            'number_name' => $number_name,
                            'pansion_name' => $pansion_name,
                            'date' => $date_arr['arr'],
                            'timestamp' => $date_arr['timestamp'],
                            'days' => $days,
                            'price' => $price_arr,
                            //'consist' => $this->makeConsist($adults, $children_ages),
                            'consist' => "$place_count местный",
                            'sum' => $price_arr['sum'],
                            'to_sea' => $to_sea,
                            'result_key' => join('', $combination) . $hotel_id . $tarrif_id . $date_arr['timestamp'] . $price_arr['sum']
                        ];


                        $results[$stamp][] = $result;
                    }
                }
            }
        }

        return $results;
    }

    private function makeConsist($adults, $children_ages)
    {
        $ch = null;
        if ($children_ages) {
            foreach ($children_ages as $age) {
                $ch .= " + 1 реб ($age)";
            }
        }
        return "$adults взр$ch";
    }

    private function formatPriceLine($priceline)
    {
        $pricetypes = [
            1 => 'взр',
            2 => 'доп',
            3 => 'реб',
            4 => 'доп реб',
            5 => 'без места'
        ];

        $output = [
            'chain' => [],
            'sum' => 0,
        ];
        foreach ($priceline as $item) {
            for ($i = 0; $i < $item['count']; $i++) {

                $type = $pricetypes[$item['pricetype']];
                $age = $item["age"];

                if ($age) $age = " ($age)";

                $output['chain'][] = [
                    'name' => $item['name'],
                    'code' => "$type$age",
                    'price' => $item['price']
                ];
                $output['sum'] += intval($item['price']);
            }
        }
        return $output;
    }

    private function getCombinations($matrix_key)
    {
        $items = [];
        foreach ($this->matrix as $item) {
            if (substr($item, 0, 3) == $matrix_key) {
                $combination = substr($item, -5);
                $combination = str_split($combination);
                foreach ($combination as &$char) {
                    $char = intval($char);
                }

                $items[] = [
                    1 => $combination[0],
                    2 => $combination[1],
                    3 => $combination[2],
                    4 => $combination[3],
                    5 => $combination[4],
                ];
            }
        }

        return $items;
    }

    private function handleCombination($combination, $prices, $children_ages)
    {

        # https://docs.google.com/spreadsheets/d/19s-pX5qOIB7hJs2wPU27VzquAZZSATNdcFf0CPymqOc/edit#gid=873825865

        $priceline = [];

        foreach ($combination as $pricetype_id => $count_of_people) {
            # Ячейка комбинации не задействована и будет пропущена
            if (!$count_of_people) continue;

            $candidate = null;

            # Пробегаем ценовой пак и хотим наткутся на такой-же $pricetype_id
            foreach ($prices as $price_record) {
                if ($price_record->pricetype_id != $pricetype_id) continue; // Не то

                # Возраст не важен
                if ($pricetype_id < 3) {
                    $candidate = [
                        'name' => $price_record->pricetype_name,
                        'price' => $price_record->price,
                        'count' => $count_of_people,
                        'pricetype' => $pricetype_id,
                        'age' => null,
                    ];
                } # Возраст важен, нужно понять, проходит ли запись
                else {
                    foreach ($children_ages as &$age) {
                        if ($age >= $price_record->age_min && $age <= $price_record->age_max) {
                            $candidate = [
                                'name' => $price_record->pricetype_name,
                                'price' => $price_record->price,
                                'count' => $count_of_people,
                                'pricetype' => $pricetype_id,
                                'age' => $age
                            ];
                            unset($age); // В рамках проверки этой комбинации, данный возраст использован в этой ячейке
                            break;
                        }
                    }
                }
            }

            if ($candidate) {
                $priceline[] = $candidate;
            } else return false;
        }

        return $priceline;
    }
}
