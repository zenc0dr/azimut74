<?php namespace Zen\Dolphin\Store;

use Zen\Dolphin\Classes\Core;
use DB;
use Carbon\Carbon;
use Zen\Dolphin\Models\City;
use Zen\Dolphin\Models\Reduct;
use Exception;

class LocalSearch extends Core
{
    private $query;
    private $result_items;
    private $list_type;
    private $labels_data; # Таблица участвующих меток и идентификаторы помеченных туров
    private $allowed_dates; # Доступные даты для типа offers,
    private $reduct_desc; # Описание скидки

    public function search($query, $list_type, $token)
    {
        $this->query = $query;
        $this->list_type = $list_type;
        $this->token = $token;

        if ($list_type == 'catalog') {
            $this->getCatalogResults();
        }
        if ($list_type == 'schedule') {
            $this->getScheduleResults();
        }

        if ($list_type == 'offers') {
            $this->getOffersResultsPrices();
        }

        return $this;
    }

    function getResultItems()
    {
        return $this->result_items;
    }

    function getLabelsData()
    {
        return $this->labels_data;
    }

    function getAllowedDates()
    {
        return $this->allowed_dates;
    }

    function getReductDesc()
    {
        return $this->reduct_desc;
    }

    private function makeLabelsData($pivot_data)
    {
        $tours_ids = $pivot_data->pluck('tour_id')->unique()->toArray();
        $pivot = DB::table('zen_dolphin_labels_tours as pivot')
            ->whereIn('pivot.tour_id', $tours_ids)
            ->join('zen_dolphin_labels as label', 'label.id', '=', 'pivot.label_id')
            ->select(
                'label.name as label_name',
                'pivot.tour_id as tour_id',
                'pivot.label_id as label_id'
            )
            ->get();

        if (!$pivot) {
            return;
        }

        $output = [];

        foreach ($pivot as $item) {
            if (!isset($output[$item->label_id])) {
                $output[$item->label_id] = [
                    'id' => $item->label_id,
                    'name' => $item->label_name,
                    'tours' => [$item->tour_id]
                ];
            } else {
                $output[$item->label_id]['tours'][] = $item->tour_id;
            }
        }

        $this->labels_data = array_values($output);
    }

    private function searchQuery($options = [])
    {
        $date_of = $this->dateToMysql($this->query['date_of']);
        $date_to = $this->dateToMysql($this->query['date_to']);

        //dd($date_of, $date_to);

        # Опция: Выезд из Саратова
        $from_saratov = @$this->query['from_saratov'];

        # Анализирует гео-объекты и находит дочерние элементы
        $geo_childrens = $this->addGeoChildrens();

        # Поиск и вывод сводных данных
        $short_query = DB::table('zen_dolphin_tours as tour')
            ->where('tour.type_id', 0)

            # Поиск по датам
            ->where(function ($query) use ($date_of, $date_to, $options) {
                # Если запрашиваются offers
                if ($this->list_type == 'offers') {
                    # Включаемая опция для подготовки массива возможных дат тура
                    if (!@$options['ignore_date']) {
                        $date = $this->dateToMysql($this->query['date']);
                        $query->where('price.date', $date);
                    }

                    $query->where('tour.id', $this->query['tour_id']);
                } # Диапазон дат
                else {
                    $query->where('price.date', '>=', $date_of);
                    $query->where('price.date', '<=', $date_to);
                }
            })
            ->where('price.price', '<>', 0)

            # Только маршруты которые начинаются с 2:336 (Саратов)
            ->where(function ($query) use ($from_saratov) {
                if ($from_saratov) {
                    $query->where('tour.waybill', 'regexp', "^2:336*");
                }
            })

            ## Гео-объекты ##
            ->where(function ($query) use ($geo_childrens, $from_saratov) {

                # Без учёта точек отправления и прибытия
                $geo_objects = $this->query['geo_objects'];
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

            # Количество дней
            ->where(function ($query) {
                $days = $this->query['days']; # Массив ночей ex:[3,4,5]
                foreach ($days as $day) {
                    $query->orWhere('tour.duration', $day);
                }
            })

            ## Ограничения по возрастам ##
            ->where(function ($query) {

                # Дети
                $children_ages = $this->query['children_ages'];

                if ($children_ages) {
                    foreach ($children_ages as $children_age) {
                        $query->orWhere(function ($subQuery) use ($children_age) {
                            $subQuery->where('price.age_min', '<=', $children_age);
                            $subQuery->where('price.age_max', '>=', $children_age);
                        });
                    }
                }

                # И взрослые
                $query->orWhereNull('price.age_min');
            })
            ->join('zen_dolphin_prices as price', 'price.tour_id', '=', 'tour.id')
            ->leftJoin('system_files as file', function ($join) {
                $join->where('file.attachment_type', 'Zen\Dolphin\Models\Tour');
                $join->where('file.field', 'preview_image');
                $join->on('file.attachment_id', '=', 'tour.id');
            });


        if ($this->list_type === 'offers') {
            $pivot_data = $short_query
                ->join('zen_dolphin_tarrifs as tarrif', 'tarrif.id', '=', 'price.tarrif_id')
                ->join('zen_dolphin_hotels as hotel', 'hotel.id', '=', 'tarrif.hotel_id')
                ->join('zen_dolphin_azcomforts as comfort', 'comfort.id', '=', 'tarrif.azcomfort_id')
                ->join('zen_dolphin_azpansions as pansion', 'pansion.id', '=', 'tarrif.azpansion_id')
                ->join('zen_dolphin_azrooms as azroom', 'azroom.id', '=', 'price.azroom_id')
                ->select([
                    'tour.id as tour_id',
                    'tour.duration as days',
                    'tour.name as tour_name',
                    'tour.waybill as waybill',
                    'azroom.name as azroom_name', # room
                    'pansion.name as pansion_name',
                    'comfort.name as comfort_name',
                    'price.date as date',
                    'price.price as price',
                    'file.disk_name as snippet',
                    'hotel.name as hotel_name',
                    'tarrif.id as tarrif_id',
                    'tarrif.name as tarrif_name'
                ])->get();

        } else {
            # Краткий запрос, без добавления дополнительных полей
            $pivot_data = $short_query->select([
                'tour.id as tour_id',
                'tour.duration as days',
                'tour.name as tour_name',
                'tour.waybill as waybill',
                'price.tarrif_id as tarrif_id',
                'price.date as date',
                'price.azroom_id as azroom_id', # room
                'price.price as price',
                'file.disk_name as snippet'
            ])->get();

            $this->makeLabelsData($pivot_data);
        }

        return $pivot_data;
    }

    private function addGeoChildrens()
    {
        $geo_objects = collect($this->query['geo_objects'])->filter(function ($item) {
            # Отбрасываем метки мест
            if (strpos($item, 'ps') !== false) {
                return false;
            }

            # Отбрасываем метки НЕ регионов
            if (strpos($item, '1:') === false) {
                return false;
            }

            return true;
        })->toArray();

        # Получить гео.объекты, родителями которых являются эти
        # regions - Тут только регионы

        $regions_ids = collect($geo_objects)->map(function ($item) {
            return explode(':', $item)[1];
        })->toArray();

        $city_geocodes = City::whereIn('region_id', $regions_ids)
            ->where('pertain_id', 0)
            ->get()
            ->map(function ($item) {
                return "2:{$item->id}";
            })->toArray();

        if (!$city_geocodes) {
            return;
        }

        return $city_geocodes;
    }

    private function getCatalogResults()
    {
        $pivot_data = $this->searchQuery();

        $output = [];
        foreach ($pivot_data as $record) {
            $tour_id = $record->tour_id;
            $price = $record->price;
            if (!isset($output[$tour_id])) {
                $date = $this->dateFromMysql($record->date);
                $snippet = ($record->snippet) ? $this->getSnippet($record->snippet) : '/plugins/zen/dolphin/assets/images/tour-no-image.jpg';
                $output[$tour_id] = [
                    'id' => $tour_id,
                    'date' => $date,
                    'days_text' => "{$record->days} " . $this->incline(['день', 'дня', 'дней'], $record->days),
                    'snippet' => $snippet,
                    'tour_name' => $record->tour_name,
                    'waybill' => $record->waybill,
                    'url' => "a/{$this->token}/$tour_id/$date",
                    'tour_id' => $tour_id,
                    'tour_date' => $record->date,
                    'source' => 'a',
                    'type' => 'catalog',
                    'price' => $price
                ];
            } else {
                # Наименьшая цена (цена от:)
                if ($price < $output[$tour_id]['price']) {
                    $output[$tour_id]['price'] = $price;
                }
            }
        }

        $this->fillWaybillsCatalog($output);

        $this->applyDiscountsMass($output);

        $this->result_items = array_values($output);
    }

    private function getSnippet($disk_name)
    {
        $image_path = $this->getDiskNamePath($disk_name);
        return $this->resize($image_path, ['width' => 500]);
    }

    private function fillWaybillsCatalog(&$items)
    {
        $wb_arr = [];
        foreach ($items as $item) {
            $waybill = $item['waybill'];
            $waybill = explode(' ', $waybill);
            foreach ($waybill as $geo_object) {
                $geo_object = explode(':', $geo_object);
                # вот тут определяется что это место $geo_object[1] = 'ps1'
                if (strpos($geo_object[1], 'ps') === false) {
                    $wb_arr[$geo_object[0]][$geo_object[1]] = null;
                } else {
                    $wb_arr['places'][$geo_object[1]] = null;
                }
            }
        }

        $models = [
            0 => ['name' => 'Country', 'ids' => []],
            1 => ['name' => 'Region', 'ids' => []],
            2 => ['name' => 'City', 'ids' => []],
            'places' => ['name' => 'Place', 'ids' => []],
        ];

        foreach ($wb_arr as $model_level => $ids) {
            $models[$model_level]['ids'] = array_keys($ids);
        }


        $pivot = [];

        foreach ($models as $model_level => $model) {
            if (!$model['ids']) {
                continue;
            }

            if ($model_level == 'places') {
                foreach ($model['ids'] as &$id) {
                    $id = intval(preg_replace('/\D/', '', $id));
                }
            }

            $geo_objects = app("Zen\Dolphin\Models\\{$model['name']}")->whereIn('id', $model['ids'])->get();
            foreach ($geo_objects as $geo_object) {
                $pivot["$model_level:{$geo_object->id}"] = $geo_object->name;
            }
        }


        foreach ($items as &$item) {
            $waybill = $item['waybill'];
            $waybill = explode(' ', $waybill);
            $item_waybill = [];
            foreach ($waybill as $geo_object) {
                if (strpos($geo_object, 'ps') !== false) {
                    $key = preg_replace('/\d:ps(\d+)/', 'places:$1', $geo_object);
                    $place = @$pivot[$key];
                    if ($place) {
                        $item_waybill[] = $pivot[$key];
                    }
                } else {
                    $item_waybill[] = $pivot[$geo_object];
                }
            }

            $item['waybill'] = $item_waybill;
        }
    }

    private function getScheduleResults()
    {

        $pivot_data = $this->searchQuery();

        $pivot = [];

        foreach ($pivot_data as $record) {
            $key = "{$record->tour_id}:{$record->date}:{$record->days}";
            if (!isset($output[$key])) {

                $snippet = ($record->snippet) ? $this->getSnippet($record->snippet) : '/plugins/zen/dolphin/assets/images/tour-no-image.jpg';
                $nights = $record->days - 1;
                $pivot[$key] = [
                    'id' => $record->tour_id,
                    'tour_name' => $record->tour_name,
                    //'days_text' => "{$record->days} " . $this->incline(['день','дня','дней'], $record->days),
                    'days' => "{$record->days} дн / $nights ноч",
                    'price' => $record->price,
                    'snippet' => $snippet,
                    'waybill' => $record->waybill,
                ];
            } else {
                if ($pivot[$key]['price'] > $record->price) {
                    $pivot[$key]['price'] = $record->price;
                }
            }
        }
        $output = [];

        foreach ($pivot as $key => $record) {
            $data = explode(':', $key);
            $tour_id = $data[0];
            $date = $data[1];
            $days = $data[2];
            $nights = $days - 1;

            $tour_date = $date;

            $date = $this->dateFromMysql($date);

            $dates_arr = $this->createDatesArray($date, $days);

            $output[] = [
                'id' => $record['id'],
                'snippet' => $record['snippet'],
                'timestamp' => $dates_arr['timestamp'],
                'source' => 'a',
                'type' => 'schedule',
                'tour_name' => $record['tour_name'],
                'waybill' => $record['waybill'],
                'date' => $dates_arr['string'],
                'tour_id' => $tour_id,
                'tour_date' => $tour_date,
                'days' => "$days дн / $nights ноч",
                //'days_text' => $record['days_text'],
                'nights' => $nights,
                'price' => $record['price'],
                'url' => "a/{$this->token}/$tour_id/$date"
            ];
        }

        $this->fillWaybillsCatalog($output);
        $this->applyDiscountsMass($output);

        $this->result_items = array_values($output);
    }

    private $datesMemory = [];

    private function createDatesArray($date, $days)
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
            'string' => "$date_1_formated ($date_1_dow) - $date_2 ($date_2_dow)",
            'timestamp' => $timestamp
        ];


        $this->datesMemory["$date:$days"] = $output;

        return $output;
    }

    private $adults_count = 0, $childrens_count = 0;

    private function getOffersResults()
    {
        $pivot_data = $this->searchQuery();

        #$this->adults_count = $this->query['adults'];
        #$this->childrens_count = count($this->query['children_ages']);

        $items = [];
        foreach ($pivot_data as $record) {

            # todo: Рассчёт распределения под вопросом
            #$allocation = $this->computeAllocation($record);
            #if(!$allocation) continue;

            $date_arr = $this->createDatesArray($record->date, $record->days);

            $items[] = [
                'type' => 'offer',
                'tour_id' => $record->tour_id,
                'pansion_name' => $record->pansion_name,
                'hotel_name' => $record->hotel_name,
                'tarrif_name' => $record->tarrif_name,
                'days' => $record->days,
                'room' => $record->azroom_name,
                'roomc' => $record->comfort_name,
                'date' => $date_arr['arr'],
                'price' => $record->price,
            ];
        }

        $this->result_items = $items;
        $this->allowed_dates = $this->computeAllowedDates();
    }

    private $matrix, $consist;

    private function getOffersResultsPrices()
    {
        try {

            $this->matrix = collect(file(base_path('plugins/zen/dolphin/storage/pac')))
                ->map(function ($item) {
                    return trim($item);
                })->toArray();

            $query = $this->query;

            $tour_id = $query['tour_id'];
            $date = $query['date'];
            $adults = intval($query['adults']);
            $children_ages = $query['children_ages'] ?? [];

            foreach ($children_ages as &$age) {
                $age = intval($age);
            }

            $date = $this->dateToMysql($date);

            $pivot = DB::table('zen_dolphin_tarrifs as tarrif')
                ->where('tour_id', $tour_id)
                ->join('zen_dolphin_tours as tour', 'tour.id', '=', 'tarrif.tour_id')
                ->join('zen_dolphin_hotels as hotel', 'hotel.id', '=', 'tarrif.hotel_id')
                ->join('zen_dolphin_azpansions as pansion', 'pansion.id', '=', 'tarrif.azpansion_id')
                ->select(
                    'tour.id as tour_id',
                    'tour.name as tour_name',
                    'tour.duration as days',
                    'tarrif.id as tarrif_id',
                    'tarrif.name as tarrif_name',
                    'tarrif.number_name as number_name',
                    'pansion.name as pansion_name',
                    'hotel.id as hotel_id',
                    'hotel.name as hotel_name',
                    'hotel.gps as hotel_gps',
                    'hotel.to_sea as to_sea',
                    'hotel.address as hotel_address',
                )
                ->orderBy('tour.id')
                ->get();

            $results = [];

            $this->consist = $this->query['adults'] . ' взр';

            if ($this->query['childrens']) {
                $this->consist .= ', ' . $this->query['childrens'] . ' реб ';
                $this->consist .= '(' . join(',', $this->query['children_ages']) . ')';
            }

            foreach ($pivot as $record) {
                $prices = $this->getPrices($record->tour_id, $record->tarrif_id, $date);
                $results = array_merge($results, $this->makeResults($record, $prices, $adults, $children_ages));
            }

            $this->applyDiscounts($results, $date);

            $this->result_items = $results;
            $this->allowed_dates = $this->computeAllowedDates();
        } catch (Exception $ex) {
            $this->addException($ex, 'LocalSearch:getOffersResultsPrices', 'Захват ошибки', [
                'query' => $query
            ]);
        }
    }

    private function applyDiscountsMass(&$results)
    {
        $tours_ids = collect($results)->pluck('tour_id')->unique()->toArray();

        $date_now = Carbon::now();

        $activators = [];

        $reduce_pivot = DB::table('zen_dolphin_tarrifs as t')
            ->whereIn('t.tour_id', $tours_ids)
            ->join('zen_dolphin_reducts_pivot as p', 'p.tarrif_id', '=', 't.id')
            ->join('zen_dolphin_activators as a', 'a.reduct_id', '=', 't.reduct_id')
            ->select(
                't.tour_id as tour_id',
                'p.date as date',
                'a.before_of as before_of',
                'a.before_to as before_to',
                'a.title as title',
                'a.accent as accent'
            )
            ->get();

        foreach ($reduce_pivot as $item) {
            $date_item = Carbon::parse($item->date);
            if ($date_item < $date_now) continue;
            $days_before = $date_item->diffInDays($date_now);

            if ($days_before > $item->before_of || $days_before < $item->before_to) continue;
            $item->days_before = $days_before;

            $activators[] = $item;
        }

        foreach ($results as &$result) {
            foreach ($activators as $activator) {
                if ($result['tour_date'] == $activator->date && $result['tour_id'] == $activator->tour_id) {
                    $result['title'] = $activator->title;
                    $result['accent'] = $activator->accent;
                }
            }
        }
    }

    private function applyDiscounts(&$results, $date)
    {
        $date_now = Carbon::now();
        $date_item = Carbon::parse($date);

        if ($date_item < $date_now) return;

        $days_before = $date_item->diffInDays($date_now);

        $tarrif_ids = collect($results)->pluck('tarrif_id')->unique()->toArray();

        $activators_data = DB::table('zen_dolphin_reducts_pivot as r')
            ->where('r.date', $date)
            ->where('a.before_of', '>=', $days_before)
            ->where('a.before_to', '<=', $days_before)
            ->whereIn('r.tarrif_id', $tarrif_ids)
            ->join('zen_dolphin_activators as a', 'a.reduct_id', '=', 'r.reduct_id')
            ->select(
                'r.tarrif_id as tarrif_id',
                'a.decrement as decrement',
                'a.title  as title',
                'a.desc as desc',
                'a.accent as accent',
                'r.reduct_id as reduct_id',
                'a.before_to as before_to'
            )
            ->get()->toArray();

        if (!$activators_data) return;

        $reduct_id = $activators_data[0]->reduct_id;
        $reduct = Reduct::find($reduct_id);
        $this->reduct_desc['common'] = $reduct->desc;

        $activators = [];

        foreach ($activators_data as $item) {

            if (!@$this->reduct_desc['single']) {
                $this->reduct_desc['single'] = $this->renderReductDesc($item, $days_before);
            }

            $activators[$item->tarrif_id] = [
                'decrement' => $item->decrement,
                'title' => $item->title,
                'accent' => $item->accent,
            ];
        }


        $peoples_count = intval($this->query['adults']) + intval($this->query['childrens']);


        foreach ($results as &$result) {

            $price = null;
            $price_old = null;

            if (isset($activators[$result['tarrif_id']])) {
                $activator = $activators[$result['tarrif_id']];
                $price = $result['price'] - ($activator['decrement'] * $peoples_count);
                $price_old = $result['price'];
            } else {
                $price = $result['price'];
            }

            $result['price_old'] = $price_old;
            $result['price'] = $price;
            $result['title'] = $activator['title'];
            $result['accent'] = $activator['accent'];
        }
    }

    private function renderReductDesc($item, $days_before)
    {
        $desc = $item->desc;
        if (!$desc) return;
        $before_to = $item->before_to;
        $D = $days_before - $before_to;

        $D .= ' ' . $this->incline(['день', 'дня', 'дней'], $D);

        return str_replace('$D', $D, $desc);
    }

    # Варианты цен для тарифа тура
    private function getPrices($tour_id, $tariff_id, $date)
    {
        return $prices = DB::table('zen_dolphin_prices as price')
            ->where('price.date', $date)
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
    }


    private function makeResults($record, $prices, $adults, $children_ages)
    {
        $childrens_count = count($children_ages);
        $pioples_count = $adults + $childrens_count;
        $ac = "$adults$childrens_count";

        $dates = $prices->pluck('date')->unique()->toArray();

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

            foreach ($price_pack as $price_pack_record) {
                if ($price_pack_record->places != $pioples_count && $price_pack_record->places - 1 != $pioples_count) continue;
                $place_packs[$price_pack_record->places][] = $price_pack_record;
            }

            foreach ($place_packs as $place_count => $place_pack) {

                $matrix_key = "$place_count$ac";
                $combinations = $this->getCombinations($matrix_key);

                foreach ($combinations as $combination) {
                    $priceline = $this->handleCombination($combination, $place_pack, $children_ages);
                    if ($priceline) {

                        $date_arr = $this->createDatesArray($date, $record->days);
                        $price_arr = $this->formatPriceLine($priceline);


                        // dd($price_arr);

                        $placement = collect($price_arr['chain'])->pluck('variant_code')->toArray();
                        $placement = join(' + ', $placement);

                        /*
                        # генерируем результат
                        $result = [
                            'tour_id' => $record->tour_id,
                            'tour_name' => $record->tour_name,
                            'tarrif_id' => $record->tarrif_id,
                            'tarrif_name' => $record->tarrif_name,
                            'number_name' => $record->number_name,
                            'hotel_id' => $record->hotel_id,
                            'hotel_name' => $record->hotel_name,
                            'hotel_address' => $record->hotel_address,
                            'hotel_to_sea' => $record->to_sea,
                            'tour_name' => $record->tour_name,
                            'consist' => $this->makeConsist($adults, $children_ages),
                            'pansion' => $record->pansion_name,
                            'date' => $ate_arr['arr'],
                            'timestamp' => $ate_arr['timestamp'],
                            'days' => $record->days,
                            'price' => $price_arr,
                            'sum' => $price_arr['sum'],
                            'result_key' =>
                                join('', $combination)
                                .$record->tour_id
                                .$record->hotel_id
                                .$ate_arr['timestamp']
                                .$price_arr['sum'],

                        ];
                        */
                        $result = [
                            'type' => 'offer',
                            'tour_id' => $record->tour_id,
                            'tarrif_id' => $record->tarrif_id,
                            'date' => $date_arr['arr'],
                            'pansion_name' => $record->pansion_name,
                            'hotel_name' => $record->hotel_name,
                            'number_name' => $record->number_name,
                            'days' => $record->days,
                            //'price_arr' => $price_arr,
                            'consist' => $this->consist,
                            'placement' => $placement,
                            'price' => $price_arr['sum'],
                        ];

                        $results[] = $result;
                    }
                }
            }
        }

        //$results = collect($results)->sortBy('sum')->toArray();
        //$results = array_values($results);


        return $results;
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
                        'allow_ages' => null
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
                                'age' => $age,
                                'allow_ages' => [
                                    'min' => $price_record->age_min,
                                    'max' => $price_record->age_max
                                ]
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

    private function formatPriceLine($priceline)
    {

        $pricetypes = [
            1 => 'Взр',
            2 => 'Доп',
            3 => 'Реб',
            4 => 'Доп реб',
            5 => 'Без места'
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

                $variant_code = $type;
                if ($item['allow_ages']) {
                    $variant_code .= '(' . $item['allow_ages']['min'] . '-' . $item['allow_ages']['max'] . ')';
                }

                $output['chain'][] = [
                    'name' => $item['name'],
                    'code' => "$type$age",
                    'variant_code' => $variant_code,
                    'price' => $item['price']
                ];
                $output['sum'] += intval($item['price']);
            }
        }

        return $output;
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

    /* Типы цен
        1 = Взрослый
        2 = Дополнительный
        3 = Детский
        4 = Детский дополнительный
        5 = Без места
    */

    private function computeAllocation($record)
    {
        $place_count = intval(preg_replace('/\D/', '', $record->azroom_name));

        if ($place_count < $this->adults_count + $this->childrens_count) return false;

        return true;
    }

    private function computeAllowedDates()
    {
        $pivot_data = $this->searchQuery(['ignore_date' => true]);

        $allowed_dates = [];

        foreach ($pivot_data as $record) {
            $allowed_dates[$record->date] = null;
        }

        $allowed_dates = collect(array_unique(array_keys($allowed_dates)))
            ->map(function ($item) {
                return $this->dateFromMysql($item);
            })->toArray();

        return $allowed_dates;
    }

}
