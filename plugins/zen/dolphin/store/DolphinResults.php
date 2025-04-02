<?php namespace Zen\Dolphin\Store;

use Zen\Dolphin\Classes\Core;
use Zen\Dolphin\Models\Hotel;
use Carbon\Carbon;
use DB;

class DolphinResults extends Core
{
    private $token;

    function render($records, $list_type, $token, $extra_options = [])
    {
        $this->token = $token;
        if($list_type == 'catalog')  return $this->renderCatalog($records);
        if($list_type == 'schedule') return $this->renderSchedule($records);
        if($list_type == 'offers')   return $this->renderOffers($records, $extra_options);
    }

    /*
     * snippet
     * source = d
     * type = catalog
     * tour_name
     * waybill
     * price_of
     */

    function renderCatalog($records)
    {
        $pivot = [];


        foreach ($records as $record) {
            if(!isset($pivot[$record['Tour']])) $pivot[$record['Tour']] = [
                'price' => 0
            ];

            foreach ($record['Offers'] as $offer) {
                $price = $offer['Price'];

                if($pivot[$record['Tour']]['price'] == 0) {
                    $pivot[$record['Tour']]['price'] = $price;
                } elseif ($pivot[$record['Tour']]['price'] > $price) {
                    $pivot[$record['Tour']]['price'] = $price;
                }
            }

            if(!isset($pivot[$record['Tour']]['snippet'])) {
                $hotel_eids[] = $record['Hotel'];
                $pivot[$record['Tour']]['snippet'] = $record['Hotel'];
            }

            if(!isset($pivot[$record['Tour']]['date'])) $pivot[$record['Tour']]['date'] = $record['Date'];
        }

        $tours_eids = array_keys($pivot);
        $tours_data = $this->getToursData($tours_eids);

        $output = [];
        foreach ($pivot as $tour_eid => $pivot_item) {

            $tour_data = $tours_data[$tour_eid];

            $output[] = [
                'id' => $tour_eid,
                'date' => $pivot_item['date'],
                'snippet' => $pivot_item['snippet'], //'/plugins/zen/dolphin/assets/images/tour-no-image.jpg',
                'tour_name' => $tour_data['tour_name'], // $this->sanitizeTourName($tour_data['tour_name']),
                'waybill' => $tour_data['waybill'],
                'url' => $this->buildUrl($tour_eid, $pivot_item['date']),
                'days_text' => null,
                'source' => 'd',
                'type' => 'catalog',
                'price' => $pivot_item['price']
            ];
        }

        $this->attachSnippets($output);

        return $output;
    }

    private function sanitizeTourName($tour_name)
    {
        //return $tour_name;
        return preg_replace('/,| тур на \d+ дн\D+$/', '', trim($tour_name));
    }

    #TODO: Не используется
    private function getDaysText($tour_name, $days)
    {
        //'days_text' => "{$record->days} " . $this->incline(['день','дня','дней'], $record->days),
        if(strpos($tour_name, 'тур на') !== false) return;
        return $this->incline(['день','дня','дней'], $days);
    }

    private function attachSnippets(&$output)
    {
        $hotel_eids = [];
        foreach ($output as $record) {
            $hotel_eids[$record['snippet']] = null;
        }

        $hotel_eids = array_keys($hotel_eids);

        $hotels = Hotel::whereIn('eid', $hotel_eids)->get();

        foreach ($hotels as $hotel) {
            $this->addGeoSnippet($hotel);
        }

        foreach ($output as &$record) {
            $this->fillGeoSnippet($record);
        }
    }

    private $snippets_memory = [
        'country' => [],
        'region' => [],
        'city' => [],
    ];

    # Данная функция создаёт массив с сниппетами, с минимальным количеством запросов
    private function addGeoSnippet($hotel)
    {
        $model_key = null;
        $model_id = null;
        if($hotel->city_id) {
            $model_key = 'city';
            $model_id = $hotel->city_id;
        }
        elseif ($hotel->region_id) {
            $model_key = 'region';
            $model_id = $hotel->region_id;
        }
        elseif ($hotel->region_id) {
            $model_key = 'country';
            $model_id = $hotel->country_id;
        }
        if(!$model_key) return;
        if(!isset($this->snippets_memory[$model_key][$model_id])) {
            $this->snippets_memory[$model_key][$model_id] = [
                'hotels_eids' => [$hotel->eid],
                'snippets' => $hotel->geo_object->thumbs,
                'count' => 0
            ];
        } else {
            if(!in_array($hotel->eid, $this->snippets_memory[$model_key][$model_id]['hotels_eids'])) {
                $this->snippets_memory[$model_key][$model_id]['hotels_eids'][] = $hotel->eid;
            }
        }
    }

    # Заполняет экземпляр вывода заменяя snippet с hotel_eid на image_path
    private function fillGeoSnippet(&$record)
    {

        $snippet_eid = $record['snippet'];
        $filled = false;
        foreach ($this->snippets_memory as $model_key => $model) {
            foreach ($model as $geo_object_key => $geo_object) {
                if(!$geo_object['snippets']) continue;
                if(in_array($snippet_eid, $geo_object['hotels_eids'])) {
                    if(!isset($geo_object['snippets'][$geo_object['count']])) {
                        $this->snippets_memory[$model_key][$geo_object_key]['count'] = 0;
                    }

                    $record['snippet'] = @$geo_object['snippets'][$geo_object['count']];
                    $filled = true;
                    $this->snippets_memory[$model_key][$geo_object_key]['count']++;
                    break;
                }
            }
        }

        if(!$filled) {
            $record['snippet'] = '/plugins/zen/dolphin/assets/images/tour-no-image.jpg';
        }
    }

    function renderSchedule($records)
    {

        $pivot = [];

        $tours_eids = [];

        # Группировка
        foreach ($records as $record) {
            $tours_eids[$record['Tour']] = null;
            $key = $record['Tour'].':'.$record['Date'].':'.$record['Nights'];
            foreach ($record['Offers'] as $offer) {
                if(!isset($pivot[$key]['price'])) {
                    $pivot[$key]['price'] = $offer['Price'];
                    $pivot[$key]['snippet'] = $record['Hotel'];
                } else {
                    if($pivot[$key]['price'] > $offer['Price']) {
                        $pivot[$key]['price'] = $offer['Price'];
                        $pivot[$key]['snippet'] = $record['Hotel'];
                    }
                }
            }
        }

        $tours_eids = array_keys($tours_eids);
        $tours_data = $this->getToursData($tours_eids);

        # Дни недели
        $dow = ['Вс','Пн','Вт','Ср','Чт','Пт','Сб'];

        $output = [];

        foreach ($pivot as $key => $bag) {
            $data = explode(':', $key);
            $tour_eid = $data[0];
            $date = $data[1];
            $nights = $data[2];

            $tour = $tours_data[$tour_eid];

            $date_1 = Carbon::parse($date);
            $date_1_formated = $date_1->format('d.m.Y');
            $timestamp = $date_1->timestamp;
            $date_1_dow = $dow[$date_1->dayOfWeek];

            $date_2 = $date_1->addDays($nights);
            $date_2_dow = $dow[$date_2->dayOfWeek];
            $date_2 = $date_2->format('d.m.Y');

            $days = $nights + 1;

            $output[] = [
                'timestamp' => $timestamp,
                'snippet' => $bag['snippet'],
                'source' => 'd',
                'type' => 'schedule',
                'tour_name' => $this->sanitizeTourName($tour['tour_name']),
                'date' => "$date_1_formated ($date_1_dow) - $date_2 ($date_2_dow)",
                'days' => "$days дн / $nights ноч",
                'nights' => $nights,
                'waybill' => $tour['waybill'],
                'price' => $bag['price'],
                'url' => $this->buildUrl($tour_eid, $date),
            ];
        }

        $this->attachSnippets($output);

        return $output;
    }

    function renderOffers($tours, $extra_options)
    {

        $date = $extra_options['date'];
        $tour_eid = $extra_options['tour_id'];

        $memory = [
            'hotels_eids' => [],
            'pansion_eids' => [],
            'room_eids' => [],
            'roomc_eids' => [],
        ];

        $output = [];

        $allowed_dates = [];

        foreach ($tours as $tour) {

            # Формирование массива доступных дат данного тура для выгрузки в календарь
            if($tour['Tour'] == $tour_eid) {
                $allowed_dates[$tour['Date']] = null;
            }

            # Фильтруем по туру и дате
            if($tour['Tour'] != $tour_eid || $tour['Date'] != $date) continue;

            # Перебор предложений
            foreach ($tour['Offers'] as $offer) {

                $memory['hotels_eids'][$tour['Hotel']] = null;
                $memory['pansion_eids'][$tour['Pansion']] = null;
                $memory['room_eids'][$offer['Room']] = null;
                $memory['roomc_eids'][$offer['RoomCat']] = null;

                $output[] = [
                    'type' => 'offer',
                    'tour_eid' => $tour_eid,
                    'hotel_name' => $tour['Hotel'],
                    'pansion_name' => $tour['Pansion'],
                    'days' => $tour['Nights'] +1,
                    'room' => $offer['Room'],
                    'roomc' => $offer['RoomCat'],
                    'date' => $this->getTourDatesArr($tour['Date'], $tour['Nights']),
                    'price' => $offer['Price'],
                ];
            }
        }


        $hotelsCollection = $this->makeNameCollection('Hotel', array_keys($memory['hotels_eids']));
        $pansionsCollection = $this->makeNameCollection('Pansion', array_keys($memory['pansion_eids']));
        $roomCollection = $this->makeNameCollection('Room', array_keys($memory['room_eids']));
        $roomcCollection = $this->makeNameCollection('RoomCategory', array_keys($memory['roomc_eids']));


        foreach ($output as &$item) {
            $item['hotel_name'] = $hotelsCollection[$item['hotel_name']];
            $item['pansion_name'] = $pansionsCollection[$item['pansion_name']];
            $item['room'] = $roomCollection[$item['room']];
            $item['roomc'] = $roomcCollection[$item['roomc']];
        }

        return [
            'items' => $output,
            'allowed_dates' => array_keys($allowed_dates)
        ];
    }

    private function makeNameCollection($modelName, $eids)
    {
        $collection = [];
        app('Zen\Dolphin\Models\\'.$modelName)->whereIn('eid',$eids)->get()
            ->map(function ($record) use (&$collection) {
                $collection[$record->eid] = $record->name;
            });
        return $collection;
    }

    # Получить даты
    private function getTourDatesArr($date, $nights)
    {
        if(@$this->datesMemory['dates'][$date.$nights]) return $this->datesMemory['dates'][$date.$nights];

        $dow = ['Вс','Пн','Вт','Ср','Чт','Пт','Сб'];

        $date_1 = Carbon::parse($date);
        $date_1_dow = $dow[$date_1->dayOfWeek];

        $date_1_formated = $date_1->format('d.m.Y');

        $date_2 = $date_1->addDays($nights);
        $date_2_dow = $dow[$date_2->dayOfWeek];
        $date_2 = $date_2->format('d.m.Y');

        $return = [
            'd1' => $date_1_formated,
            'd1d' => $date_1_dow,
            'd2' => $date_2,
            'd2d' => $date_2_dow
        ];

        $this->datesMemory['dates'][$date.$nights] = $return;

        return $return;
    }

    ############## HELPERS ##############
    # Функция добывающая из тура расписание
    # $tour - Тур, $city_in_search_name - Город в поиске, например 'Санкт-Петербург'
    function getWaybill($tour, $city_in_search_name = null)
    {
        if(!@$tour['Parts']['Fields'][0]) return null;
        $out = [];
        foreach ($tour['Parts']['Fields'][0] as $field) {
            $landmarks = $field['Info']['Landmarks'];
            if(!$landmarks) continue;
            $areas = $field['Info']['Areas'];

            if($city_in_search_name && count($areas) == 1 && $areas[0] == $city_in_search_name) {
                $areas = '';
            } else {
                $areas = ' ('.join(', ', $areas).')';
            }

            $out[] = join(', ', $landmarks).$areas;
        }
        return $out;
    }

    private function getToursData($tours_eids)
    {
        $dolphin_tours = $this->store('Dolphin')->getTours($tours_eids);

        $tours_data = [];
        foreach ($dolphin_tours as $dolphin_tour) {
            $tours_data[$dolphin_tour['Id']] = [
                'id' => $dolphin_tour['Id'],
                'tour_name' => $dolphin_tour['Name'],
                'waybill' => $this->getWaybill($dolphin_tour)
            ];
        }
        return $tours_data;
    }

    private function buildUrl($tour_eid, $date)
    {
        return "d/{$this->token}/$tour_eid/$date";
    }
}
