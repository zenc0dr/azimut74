<?php namespace Mcmraak\Rivercrs\Traits;

use Mcmraak\Rivercrs\Models\Motorships as Ship;
use Mcmraak\Rivercrs\Models\Cabins as Cabin;
use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use Mcmraak\Rivercrs\Models\Pricing;
use Mcmraak\Rivercrs\Models\Towns as Town;
use DB;
use Mcmraak\Rivercrs\Classes\CacheSettings;
use Input;
use Log;
use Mcmraak\Rivercrs\Models\Log as JLog;
use Carbon\Carbon;

trait Waterway {


    # Водоход - Кеш цен
    # http://azimut.dc/rivercrs/api/v2/parser/waterwayCruisesCache
    # http://azimut.dc/rivercrs/api/v2/parser/waterwayCruisesCache?id=10038
    public function waterwayCruisesCache()
    {
        $id = Input::get('id');
        if(!$id) {
            $ww_cruises = $this->cacheWarmUp('waterway-cruises', 'array');
            $cache_time = CacheSettings::get('waterway_prices');
            $ids = array_keys($ww_cruises);
            $live_ids = [];
            $url = 'https://api.vodohod.com/json/v2/cruise-prices.php?pauth=kefhjkdRgwFdkVHpRHGs&cruise=';

            foreach ($ids as $id){
                if(!$this->testTimeCache($url.$id, $cache_time)){
                    $live_ids[] = $id;
                }
            }
            $this->json(['ids' => $live_ids]);
            return;
        }

        $error = $this->cacheWarmUp('waterway-prices', 'check', ['id' => $id], 10);

        if($error) {
            $this->json(['error' => $error]);
        } else {
            $this->json(['html' => "Детальный маршрут круиза #$id"]);
        }
    }

    # Водоход - Кеш маршрутов
    # http://azimut.dc/rivercrs/api/v2/parser/waterwayRoutesCache
    # http://azimut.dc/rivercrs/api/v2/parser/waterwayRoutesCache?id=10130
    public function waterwayRoutesCache()
    {
        $id = Input::get('id');

        if(!$id) {
            $ww_cruises = $this->get('json',
                'https://api.vodohod.com/json/v2/cruises.php',
                ['pauth' => 'kefhjkdRgwFdkVHpRHGs'], CacheSettings::get('waterway_route'));
            $cache_time = CacheSettings::get('waterway_cruises');
            $ids = array_keys($ww_cruises);
            $live_ids = [];
            $url = 'https://api.vodohod.com/json/v2/cruise-days.php?pauth=kefhjkdRgwFdkVHpRHGs&cruise=';

            foreach ($ids as $id){
                if(!$this->testTimeCache($url.$id, $cache_time)){
                    $live_ids[] = $id;
                }
            }
            $this->json(['ids' => $live_ids]);
            return;
        }

        $error = $this->cacheWarmUp('waterway-route', 'check', ['id' => $id], 10);

        if($error) {
            $this->json(['error' => $error]);
        } else {
            $this->json(['html' => "Детальный маршрут круиза #$id"]);
        }

    }

    # http://azimut.dc/rivercrs/debug/Getter@waterwayMotorshipsSeeder #V
    public function waterwayMotorshipsSeeder()
    {
        $dump = $this->cacheWarmUp('waterway-motorships', 'array');

        foreach ($dump as $id => $item) {
            $name = $item['name'];
            $type = $item['type'];
            //$image = $item['image'];
            $desc = $item['description'];
            //$motorship_name = 'Теплоход "'.$name.'" (проект '.$type.')';

            $ship = Ship::where('waterway_id', $id)->first();
            if($ship) continue;

            if(!$ship)
                $ship = Ship::where('name', 'like', "%$name%")->first();

            if(!$ship) {
                $ship = new Ship;
                $ship->name = $name;
                $ship->desc = $desc;
                $ship->add_a = '';
                $ship->add_b = '';
                $ship->booking_discounts = '';
                $ship->social_discounts = '';
                $ship->youtube = '';
                $ship->banner = '';
                $ship->techs = [];
                $ship->waterway_id = $id;
            } else {
                $ship->waterway_id = $id;
            }

            $ship->save();
        }
    }

    # http://azimut.dc/rivercrs/debug/Getter@waterwayDecksSeeder
    public function waterwayDecksSeeder()
    {
        $cruises = $this->cacheWarmUp('waterway-cruises', 'array');
        $motorships = [];

        foreach ($cruises as $cruise_id => $cruise){

            $motorship_id = $cruise['motorshipId'];

            $prices = $this->cacheWarmUp('waterway-prices', 'array', [
                'pauth' => 'kefhjkdRgwFdkVHpRHGs',
                'id' => $cruise_id
            ]);

            if(!$prices) continue;
            if(!isset($prices['tariffs']))
            {
                JLog::add('error', 'trait:Waterway@waterwayDecksSeeder',
                    print_r($cruise, 1),
                    'Отсутствую тарифы в круизе:'.$cruise_id);
                continue;
            }


            foreach ($prices['tariffs'] as $a){
                foreach ($a['prices'] as $b){
                    $deck_name = $b['deck_name'];
                    if(isset($motorships[$motorship_id])){
                        if(!in_array($deck_name, $motorships[$motorship_id])) {
                            $motorships[$motorship_id][] = $deck_name;
                        }
                    } else {
                        $motorships[$motorship_id][] = $deck_name;
                    }
                }
            }
        }

        foreach ($motorships as $motorship_id => $decks){
            foreach ($decks as $item) {
                #TODO:DEPRICATED
                #$deck =  DB::table('mcmraak_rivercrs_decks')->where('name', 'like', "%$item%")->first();
                $deck = $this->getDeck($item);

                if(!$deck) {
                    DB::table('mcmraak_rivercrs_decks')->insert([
                        'name' => $item.' палуба',
                        'sort_order' => 10
                    ]);
                }
            }
        }
    }

    # http://azimut.dc/rivercrs/debug/Getter@testwwChecki
    /*
    function testwwCheckin()
    {
        $checkin = Checkin::find(26734);
        dd(DB::table('mcmraak_rivercrs_pricing')->where('checkin_id', $checkin->id)->count());
    }*/

    public function waterwayCabinsSeeder()
    {
        # http://azimut.dc/rivercrs/debug/Getter@waterwayCabinsSeeder?id=init
        # http://azimut.dc/rivercrs/debug/Getter@waterwayCabinsSeeder?debug=7868


        $debug = Input::get('debug');
        $id = Input::get('id'); if($debug) $id = $debug;

        $cruises = $this->cacheWarmUp('waterway-cruises', 'array');

        if($id == 'init') {
            $ids = [];
            foreach ($cruises as $cruise_id => $cruise) {
                $prices = $this->cacheWarmUp('waterway-prices', 'array', [
                    'pauth' => 'kefhjkdRgwFdkVHpRHGs',
                    'id' => $cruise_id
                ]);
                if($prices) $ids[] = $cruise_id;
            }
            $this->json($ids);
            return;
        }

        if(!$debug && $this->waterwayId($id)) {
            $this->json([
                'message' => "Заезд:$id уже обработан"
            ]);
            return;
        };

        $cruise = @$cruises[$id];

        if(!$cruise) {
            $this->json([
                'message' => "id круиза не существует"
            ]);
            return;
        }

        $ship_eds_id = $cruise['motorshipId'];

        if(!$ship_eds_id) {
            $this->json([
                'message' => "id корабля не существует"
            ]);
            return;
        }

        $motorship = Ship::where('waterway_id', (int) $ship_eds_id)->first();

        if(!$motorship) {
            die('no find motorship:'.$ship_eds_id);
        };

        $motorship_name = $motorship->name;
        $cruise_name = $cruise['name']; // Москва — Астрахань (2&nbsp;дня) — Москва

        $prices = $this->cacheWarmUp('waterway-prices', 'array', ['id' => $id]);

        if(!$prices || !isset($prices['tariffs']))
        {
            JLog::add('error', 'trait:Waterway@waterwayDecksSeeder',
                print_r($cruise, 1),
                'Отсутствую цены в заезде:'.$id);
            $checkin = Checkin::where('eds_code','waterway')
                ->where('eds_id', $id)
                ->first();
            if($checkin) {
                $checkin->active = 0;
                $checkin->save();
            }
            return;
        }

        $checkin = Checkin::where('eds_code','waterway')
            ->where('eds_id', $id)
            ->first();

        $routes = $this->cacheWarmUp('waterway-route', 'array', ['id' => $id]);

        if($routes && !isset($routes['error'])) {
            $desc_1 = $this->wwGraph($routes, $cruise['dateStart']);
            $dates = $this->wwDates($routes, $cruise['dateStart'], $cruise['dateStop']);
            $date = $dates['date'];
            $dateb = $dates['dateb'];
            $waybill = $this->wwRoutesHandler($cruise, $id, $routes);
        } else {

            $mi = $this->wwMinimalInfo($cruise);
            $date = $mi['date'];
            $dateb = $mi['dateb'];
            $waybill = $mi['waybill'];
            $desc_1 = $cruise['classDescription'] ?? '';
        }

        /* $waybill = [
            ['town' => 562, excursion => '', bold => 0]
        ] */


        if(!$waybill || count($waybill) < 2) {
            $message = "Заезд:$id - Некорректный маршрут";
            JLog::add('check', 'waterwayCabinsSeeder', $message, $message);
            $this->json([
                'message' => $message
            ]);
            return;
        }


        if(!$checkin){
            $days = $cruise['days'];
            $this->daysDiffCheck($days, $id);
            $checkin = new Checkin;
            $checkin->date = $date;
            $checkin->dateb = $dateb;
            $checkin->days = $days;
            $checkin->motorship_id = $motorship->id;
            $checkin->active = 1;
            $checkin->desc_1 = $desc_1;
            $checkin->eds_code = 'waterway';
            $checkin->eds_id = (int) $id;
            $checkin->waybill_id = $waybill;
            $checkin->save();
        } else {
            if(count($waybill) > count($checkin->waybill_id)) {
                #DB::table('mcmraak_rivercrs_waybills')->where('checkin_id', $checkin->id)->delete();
                $checkin->waybill_id = $waybill;
            } else {
                $checkin->waybill_id = 'none';
            }
            $checkin->date = $date;
            $checkin->dateb = $dateb;
            $checkin->desc_1 = $desc_1;
            $checkin->save();
        }

        foreach ($prices['tariffs'] as $a) {
            if($a['tariff_name']!='Тариф Взрослый') continue;

            $prices = $this->wwMinimalPrice($a['prices']);

            foreach ($prices as $b) { // Создание кабины, палубы, цены
                $deck_name = $b['deck_name'];

                $deck =  DB::table('mcmraak_rivercrs_decks')->where('name', 'like', "%$deck_name%")->first();
                $category = $b['rt_name'];

                if($this->isCabinNotLet($category, $motorship->id)) continue;
                $price_value = $b['price_value'];

                $cabin = Cabin::where('waterway_name', $category)
                    ->where('motorship_id', $motorship->id)
                    ->first();

                if(!$cabin)
                $cabin = Cabin::where('category', $category)
                    ->where('motorship_id', $motorship->id)
                    ->first();

                if(!$cabin) {
                    $cabin = new Cabin;
                    $cabin->motorship_id = $motorship->id;
                    $cabin->category = $category;
                    $cabin->waterway_name = $category;
                    $cabin->desc = $b['rp_name'];
                    $cabin->save();
                }

                $this->deckPivotCheck($cabin->id, $deck->id);
                $this->updateCabinPrice($checkin->id, $cabin->id, (int) $price_value);
            }
        }

        # Проверка
        $this->testCheckin($id, 'waterway');

        # Удаление кеша (В очередь)
        # app('\Mcmraak\Rivercrs\Controllers\Checkins')->recacheChekin($checkin->id);

        # Поисковый кеш
        //$checkin->updateSearchCache();

        # Complite and create progress-message
        $this->json([
            'message' => 'Обработка: '.$motorship_name.' Заезд #'.$id.': '.$cruise_name
        ]);
    }

    public function wwMinimalInfo($cruise)
    {
        $mini_route = explode(' — ', $cruise['name']);
        $waybill = [];
        foreach ($mini_route as $town_name) {
            if(strpos($town_name,')') > 0) {
                $town_name = preg_replace('/\([^()]+\)/', '', $town_name);
            }
            $town_name = trim($town_name);
            $town_id = $this->getTownId($town_name);
            $waybill[] = [
                'town' => $town_id,
                'excursion' => '',
                'bold' => 0,
            ];
        }
        $waybill[0]['bold'] = 1;
        $waybill[count($waybill)-1]['bold'] = 1;

        if(count($waybill) < 2) return false;

        return [
            'waybill' => $waybill,
            'date' => $cruise['dateStart'].' 00:00:00',
            'dateb' => $cruise['dateStop'].' 00:00:00',
        ];
    }

    public function wwDates($routes, $date_start, $date_stop)
    {
        return [
            'date' => $date_start.' '.$routes[0]['timeStop'],
            'dateb' => $date_stop.' '.$routes[count($routes)-1]['timeStart'],
        ];
    }

    public function wwRoutesHandler($cruise, $cruise_id, $routes)
    {
        $waybill = [];
        $alt_routes = explode(' — ', $cruise['name']);


        if(!isset($routes['error'])) {
            $routes_end = count($routes) - 1;
            $routes_i = 0;
            foreach ($routes as $route){

                if(!isset($route['portName'])){
                    JLog::add('error',
                        'trait:Waterway@waterwayCabinsSeeder',
                        print_r($routes,1),
                        'Отсутствует город в маршруте, заезд#'.$cruise_id
                    );
                    continue;
                }

                $waybill[] = [
                    'town' => $this->getTownId($route['portName'], 'waterway'),
                    'excursion' => '[ День: '.$route['day'].' ] '.$route['excursion'],
                    'bold' => (in_array($route['portName'],$alt_routes) || $routes_i == 0 || $routes_i == $routes_end)?1:0
                ];
                $routes_i++;
            }
        } else { # Если водоход не даёт маршрут берём из круиза
            foreach ($alt_routes as $route){
                $waybill[] = [
                    'town' => $this->getTownId($route, 'waterway'),
                    'excursion' => '',
                    'bold' => 0
                ];
            }
        }

        return $waybill;
    }

    public function wwMinimalPrice($prices)
    {
        return $prices;

        $return = $prices;

        foreach ($prices as $price)
        {
            $category = $price['rt_name'];
            $price_value = $price['price_value'];
            $key = $this->wwDelLarger($category, $price_value, $prices);
            if($key) unset($return[$key]);
        }

        return $return;

    }

    public function wwDelLarger($category, $price_value, $prices)
    {
        $key = 0;
        foreach ($prices as $price)
        {
            $item_category = $price['rt_name'];
            $item_price_value = $price['price_value'];
            if($item_category == $category) {
                if($item_price_value > $price_value) {
                    return $key;
                }
            }
            $key++;
        }
        return false;
    }

    public function wwGraph($routes, $start)
    {
        if(isset($routes['error'])) return;
        $days_of_week = ['вс', 'пн', 'вт', 'ср', 'чт', 'пт', 'сб'];
        $return = [];
        $return[] = '<table><tbody>';
        $return[] = "<tr><td>День</td><td>Стоянка</td><td>Программа дня</td></tr>";
        foreach ($routes as $route)
        {
            $date = Carbon::parse($start);
            $day = $route['day'];
            $port = $route['portName'];
            $excursion = $route['excursion'];
            $time_start = $route['timeStart'];
            $time_stop = $route['timeStop'];
            $time = $this->wwGraphTimeFormat($time_start, $time_stop);
            $ex_date = $date->addDays(intval($day)-1);
            $day_of_week = $days_of_week[$ex_date->dayOfWeek];
            $ex_date = $ex_date->format('d.m.Y');
            $return[] = "<tr><td>$day <br>$ex_date<br>$time ($day_of_week)</td><td>$port</td><td>$excursion</td></tr>";
        }
        $return[] = '</tbody></table>';
        $return = join("\n", $return);
        return $return;
    }

    public function wwGraphTimeFormat($time_start, $time_stop)
    {
        if($time_start=='00:00:00') {
            $time_stop = Carbon::parse($time_stop);
            return '<span class="ww_time">Отправление в '.$time_stop->format('H:i').'</span>';
        }
        if($time_stop=='00:00:00') {
            $time_start = Carbon::parse($time_start);
            return '<span class="ww_time">Прибытие в '.$time_start->format('H:i').'</span>';
        }

        $time_start = Carbon::parse($time_start);
        $time_stop = Carbon::parse($time_stop);
        return '<span class="ww_time">'.
            $time_start->format('H:i').
            ' - '.
            $time_stop->format('H:i').
            '</span>';
    }

    # Статусы кабин
    public function statusesWaterway()
    {
        //$cruises = $this->cacheWarmUp('waterway-cruises', 'array');
        //dd($cruises[7854]);
        $prices = $this->cacheWarmUp('waterway-prices', 'array', [
            'pauth' => 'kefhjkdRgwFdkVHpRHGs',
            'id' => 7854
        ]);
        dd($prices);
//        $ships = $this->cacheWarmUp('waterway-motorships', 'array');
//        dd($ships);

    }
}
