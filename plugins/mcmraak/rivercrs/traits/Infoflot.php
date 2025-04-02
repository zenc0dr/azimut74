<?php namespace Mcmraak\Rivercrs\Traits;

use Mcmraak\Rivercrs\Classes\CacheSettings;
use Exception;
use Log;
use Mcmraak\Rivercrs\Models\Log as JLog;
use Input;
use Carbon\Carbon;
use DB;
use Mcmraak\Rivercrs\Models\Motorships as Ship;
use Mcmraak\Rivercrs\Models\Cabins as Cabin;
use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use Mcmraak\Rivercrs\Models\Pricing;
use Mcmraak\Rivercrs\Models\Towns as Town;
use Mcmraak\Rivercrs\Models\Decks as Deck;
use Mcmraak\Rivercrs\Classes\Ids;
use Cache;

trait Infoflot
{

    function getInfoflotShipsIds()
    {
        $ships_ids = $this->cacheWarmUp('infoflot-ships', 'array');
        $ships_ids = array_keys($ships_ids);

        /* TODO: Перепуташки
        $skipped_ids = CacheSettings::get('not_let_eds_data');
        foreach ($skipped_ids as $eds) {
            if($eds['eds_id'] == 'infoflot') {
                $eds_ids = $eds['edsids'];
                $eds_ids = str_replace(' ', '', $eds_ids);
                $eds_ids = explode(',', $eds_ids);
                $ships_ids = array_diff($ships_ids, $eds_ids);
            }
        }
        */

        return $ships_ids;
    }

    # http://azimut.dc/rivercrs/api/v2/parser/infoflotTours
    function infoflotTours()
    {
        $ship_id = Input::get('id');

        $cache = new Ids('infoflot_cache', CacheSettings::get('infoflot_tours'));

        if(!$ship_id) {
            Cache::forget('infoflotTours.ships');
            $ships_ids = $this->getInfoflotShipsIds();
            $ships_ids = $cache->liveIds($ships_ids);
            $this->json(['ids' => $ships_ids]);
            return;
        }

        $cache_id = 'tour:'.$ship_id;

        $id_exist = $cache->test($cache_id);

        if($id_exist) {
            $this->json(['html' => "Кеш уже обработан"]);
            return;
        }

        if($this->checkBadInfoflotShip($ship_id)) {
            $cache->addError($cache_id);
            $this->json(['html' => "Теплоход пропущен #$ship_id"]);
            return;
        }

        $tours = $this->cacheWarmUp('infoflot-tours', 'array', ['id' => $ship_id], 20, 'infoflot');

        if(@$tours['error']) {
            $this->json(['error' => $tours['error']]);
            $cache->addError($cache_id);
        } else {
            $tours_ids = array_keys($tours);
            $ids = [];
            foreach ($tours_ids as $cruise_id) {
                $ids[] = "$ship_id:$cruise_id";
            }

            $cache->addIds('route:', $ids);
            $this->json(['html' => "Кеш круизов теплохода #$ship_id"]);
        }
    }

    function checkBadInfoflotShip($ship_eds_id=null, $return_ids = false)
    {
        # Проверка на исключение
        if (Cache::has('infoflotTours.ships')) {
            $bad_ships = Cache::get('infoflotTours.ships');
            if($return_ids) return $bad_ships;
        } else {
            $skipped_ids = CacheSettings::get('not_let_eds_data');
            $ship_ids = [];
            foreach ($skipped_ids as $skip) {
                if($skip['eds_id'] == 'infoflot') {
                    $s_ids = $skip['edsids'];
                    $s_ids = str_replace(' ', '', $s_ids);
                    $ship_ids += explode(',', $s_ids);
                }
            }
            $bad_ships = Ship::whereIn('id', $ship_ids)->where('infoflot_id', '<>', 0)->pluck('infoflot_id')->toArray();
            Cache::add('infoflotTours.bad_ships', $bad_ships, 1440); # Сутки
            if($return_ids) return $bad_ships;
        }

        if(!$ship_eds_id) return true;
        if(array_search($ship_eds_id, $bad_ships)!==false) {
            return true;
        } else {
            return false;
        }
    }



    # http://azimut.dc/rivercrs/api/v2/parser/infoflotRoutes
    function infoflotRoutes()
    {
        $id = Input::get('id'); // 345:34532

        $cache = new Ids('infoflot_cache', CacheSettings::get('infoflot_tours'));

        if(!$id) {
            $ids = $cache->get('route:', true);
            $this->json(['ids' => $ids]);
            return;
        }

        if($this->checkBadInfoflotShip(@explode($id)[0])) {
            $this->json(['html' => "Маршрут пропущен"]);
            return;
        }

        $cache_id = "route:$id";

        $id_exist = $cache->test($cache_id);

        if($id_exist) {
            $this->json(['html' => "Кеш уже обработан"]);
            return;
        }

        // infoflot-routes
        $error = $this->cacheWarmUp('infoflot-routes', 'check', ['id' => $id], 10, 'infoflot');

        if($error) {
            $this->json(['error' => $error]);
            $cache->addError($cache_id);
        } else {
            $this->json(['html' => "Детальный маршрут круиза #$id"]);
        }
    }

    # http://azimut.dc/rivercrs/api/v2/parser/infoflotCabins?id=475
    function infoflotCabins()
    {
        $ship_id = Input::get('id');
        $cache = new Ids('infoflot_cache', CacheSettings::get('infoflot_tours'));

        if(!$ship_id) {
            $ships_ids = $this->getInfoflotShipsIds();
            $this->json(['ids' => $ships_ids]);
            return;
        }

        $cache_id = "cabin:$ship_id";

        $id_exist = $cache->test($cache_id);
        if($id_exist) {
            $this->json(['html' => "Кеш уже обработан"]);
            return;
        }

        $error = $this->cacheWarmUp('infoflot-cabins', 'check', ['id' => $ship_id], 10, 'infoflot');

        if($error) {
            $this->json(['error' => $error]);
            $cache->addError($cache_id);
        } else {
            $this->json(['html' => "Кеш кабин теплохода #$ship_id"]);
        }
    }

    # TODO: Пока не используется
    # http://azimut.dc/rivercrs/api/v2/parser/infoflotCabinsDesc?id=28:Люкс 1
    function infoflotCabinsDesc()
    {
        $id = Input::get('id');
        $cache = new Ids('infoflot_cache', CacheSettings::get('infoflot_tours'));

        if(!$id) {
            $ids = [];
            $ships_ids = $this->getInfoflotShipsIds();
            foreach ($ships_ids as $ship_id) {
                if(!$cache->isValid("cabin:$ship_id")) continue;
                $cabins = $this->cacheWarmUp('infoflot-cabins', 'array', ['id' => $ship_id], 10);
                foreach ($cabins as $cabin) {
                    if(!@$cabin['name']) continue;
                    $ids[] = "$ship_id:".$cabin['name'];
                }
            }
            $this->json(['ids' => $ids]);
            return;
        }

        $cache_id = "cabindesc:$id";

        $id_exist = $cache->test($cache_id);
        if($id_exist) {
            $this->json(['html' => "Кеш уже обработан"]);
            return;
        }

        $error = $this->cacheWarmUp('infoflot-cabinsdesc', 'check', ['id' => $id], 10, 'infoflot');

        if($error) {
            $this->json(['error' => $error]);
            $cache->addError($cache_id);
        } else {
            $this->json(['html' => "Кеш описания кабин #$id"]);
        }
    }

    # http://azimut.dc/rivercrs/api/v2/parser/infoflotMotorshipsSeeder?id=init
    # http://azimut.dc/rivercrs/api/v2/parser/infoflotMotorshipsSeeder?id=325
    function infoflotMotorshipsSeeder()
    {
        $debug = Input::get('debug');
        $id = Input::get('id'); if($debug) $id = $debug;

        if($id == 'init') {
            $this->json($this->getInfoflotShipsIds());
            return;
        }

        if($this->checkBadInfoflotShip($id)) {
            $this->json(['html' => "Теплоход пропущен"]);
            return;
        }

        $ships = $this->cacheWarmUp('infoflot-ships', 'array', null, null, 'infoflot');

        $ship_name = @$ships[$id];

        if(!$ship_name) {
            $this->json(['error' => "Теплоход $id не обнаружен"]);
            return;
        }

        $ship = Ship::where('name', 'like', "%$ship_name%")->first();

        if(!$ship) {
            $ship = new Ship;
            $ship->name = $ship_name;
            $ship->desc = '';
            $ship->add_a = '';
            $ship->add_b = '';
            $ship->booking_discounts = '';
            $ship->social_discounts = '';
            $ship->youtube = '';
            $ship->banner = '';
            $ship->techs = [];
            $ship->infoflot_id = $id;
        } else {
            $ship->infoflot_id = $id;
        }

        $ship->save();

        $this->json([
            'message' => 'Обработка: Теплоход: '.$ship->name
        ]);
    }

    # http://azimut.dc/rivercrs/api/v2/parser/infoflotSeeder?id=init&debug=true
    # http://azimut.dc/rivercrs/api/v2/parser/infoflotSeeder?id=498:307336&debug=true

    # http://azimut.dc/rivercrs/api/v2/parser/infoflotSeeder?id=41:337164&debug=true
    # 41:337164


    # http://azimut.dc/rivercrs/api/v2/parser/infoflotSeeder?id=551:318930&debug=true // Без $route
    public $id;
    function infoflotSeeder()
    {
        $debug = Input::get('debug');
        $id = $this->id = Input::get('id');

        if($id == 'init') {
            $cache = new Ids('infoflot_cache');
            $ids = $cache->get('route:', true);
            $this->json($ids);
            return;
        }

        if(!$debug)
            if($this->infoflotId($id)) {
            $this->json([
                'message' => "Заезд:$id уже обработан"
            ]);
            return;
        };

        try {

            $cache = new Ids('infoflot_cache');

            $arr = explode(':', $id);
            $ship_id = $arr[0];
            $cruise_id = $arr[1];

            $cruise = $this->cacheWarmUp('infoflot-tours', 'array', ['id' => $ship_id]);
            $cruise = @$cruise[$cruise_id];

            if(!$cruise) {
                $this->json([
                    'message' => "Кеш пропускется"
                ]);
                return;
            }


            $route = null;
            $valid_route = $cache->isValid('route:'.$id);
            if($valid_route) {
                $route = $this->cacheWarmUp('infoflot-routes', 'array', ['id' => $id]);
                if(isset($route['error'])) {
                    $route = null;
                }
            }

            # Ship
            $ship = $this->cacheWarmUp('infoflot-ships', 'array');
            $ship = $ship[$ship_id];
            $ship = $this->getMotorship($ship, 'infoflot_id', $ship_id);

            # Cabins
            if (!$cache->isValid("cabin:$ship_id")) {
                $error = "Ошибка: Отсутствует кеш кабины для теплохода {$ship->name}";
                $this->json([
                    'message' => $error
                ]);
                JLog::add('error', 'infoflotSeeder()', $error, 'Ошибка при обработке круиза:' . $cruise_id);
                return;
            }

            $cabins = $this->cacheWarmUp('infoflot-cabins', 'array', ['id' => $ship_id]);

            # $cruise
            # $route
            # $ship
            # $cabins

            # dd($cruise, $route, $cabins);

            $checkin = Checkin::where('eds_code', 'infoflot')
                ->where('eds_id', $cruise_id)
                ->first();

            $this->daysDiffCheck($cruise['days'], $cruise_id);


            if($route && count($route) > 1) {
                $waybill = $this->getInfoflotWaybill($cruise, $route);
                $dates = $this->getInfoflotDates($route); #ex: $dates->date: '2019-10-11 17:30:00'
            } else {
                $waybill = $this->getInfoflotWaybillMin($cruise);
                $dates = $this->getInfoflotDatesMini($cruise);
            }

            if (!$checkin) $checkin = new Checkin;

            $checkin->date = $dates->date;
            $checkin->dateb = $dates->dateb;
            $checkin->desc_1 = $this->infoflotDesignSchedule($route);
            $checkin->motorship_id = $ship->id;
            $checkin->active = 1;
            $checkin->eds_code = 'infoflot';
            $checkin->eds_id = (int) $cruise_id;
            $checkin->waybill_id = $waybill;
            $checkin->save();
            $this->fillInfoflotPrices($cruise, $cabins, $checkin->id, $ship);


        } catch (Exception $ex) {
                $error = $ex->getMessage();
                JLog::add('error', 'infoflotSeeder', $error, 'Ошибка при обработке круиза:'.$cruise_id);
                $this->json([
                    'message' => 'Ошибка при обработке круиза:'.$cruise_id
                ]);
                return;
        }


        # Проверка
        $this->testCheckin($id, 'infoflot');

        # Удаление кеша (В очередь)
        # app('\Mcmraak\Rivercrs\Controllers\Checkins')->recacheChekin($checkin->id);

        # Поисковый кеш
        //$checkin->updateSearchCache();

        $this->json([
            'message' => 'Обработка: Заезд #'.$id." [checkin_id:{$checkin->id}]"
        ]);

    }

    # Получить маршрут
    function getInfoflotWaybill($cruise, $route)
    {
        $bold = $cruise['route'];
        $bold = explode(' – ', $bold);

        $waybill = [];
        $key = 0;
        $max = count($route) - 1;
        foreach ($route as $point) {
            $town_id = $this->getTownId($point['city'], 'infoflot');
            $waybill[] = [
                'town' => $town_id,
                'excursion' => '',
                'bold' => (in_array($point['city'], $bold) || $key==0 || $key == $max)?1:0
            ];
            $key++;
        }

        return $waybill;
    }

    # Получить маршрут минимальный
    function getInfoflotWaybillMin($cruise)
    {
        $route = $cruise['cities'];
        $route = explode(' – ', $route);
        $bold = $cruise['route'];
        $bold = explode(' – ', $bold);

        $waybill = [];
        $key = 0;
        $max = count($route) - 1;
        foreach ($route as $point) {
            $town_id = $this->getTownId($point, 'infoflot');
            $waybill[] = [
                'town' => $town_id,
                'excursion' => '',
                'bold' => (in_array($point, $bold) || $key==0 || $key == $max)?1:0
            ];
            $key++;
        }

        return $waybill;
    }

    # Получить даты из маршрута
    function getInfoflotDates($route)
    {
        $date = array_shift($route);
        $date = $this->mysqlDate($date['date_start']).' '.$date['time_start'].':00';
        $dateb = array_pop($route);
        $dateb = $this->mysqlDate($dateb['date_end']).' '.$dateb['time_end'].':00';
        return (object) [
            'date' => $date,
            'dateb' => $dateb
        ];
    }

    # Получить даты из круиза, когда маршрута нет
    function getInfoflotDatesMini($cruise)
    {
        $date = $this->mysqlDate($cruise['date_start']).' '.$cruise['time_start'].':00';
        $dateb = $this->mysqlDate($cruise['date_end']).' '.$cruise['time_end'].':00';
        return (object) [
            'date' => $date,
            'dateb' => $dateb
        ];
    }

    # Оформление таблицы расписания в заезде
    function infoflotDesignSchedule($route)
    {
        try
        {

            $gama_cruise_route = [];
            foreach ($route as $point) {

                $town_name = $point['city'];
                $start_time = $point['date_start'].' '.$point['time_start'];
                $end_time = $point['date_end'].' '.$point['time_end'];


                $gama_cruise_route[] = [
                    'town' => $town_name,
                    'start' => $start_time,
                    'end' => $end_time
                ];
            }

            $table_data = [];

            foreach ($gama_cruise_route as $item) {

                $town = $item['town']; # Нижний Новгород
                $full_date_1 = Carbon::parse($item['start']);
                $full_date_2 = Carbon::parse($item['end']);
                $diff_in_days = $full_date_2->diffInDays($full_date_1);
                $stay = $full_date_2->diffInSeconds($full_date_1);
                $stay = gmdate('H:i', $stay);

                if ($diff_in_days == 0) {
                    $table_data[] = [
                        'date' => $full_date_1->format('d.m.Y'),
                        'town' => $town,
                        'arrival' => $full_date_1->format('H:i'),
                        'stay' => $stay,
                        'departure' => $full_date_2->format('H:i'),
                    ];
                } else {
                    $table_data[] = [
                        'date' => $full_date_1->format('d.m.Y'),
                        'town' => $town,
                        'arrival' => $full_date_1->format('H:i'),
                        'stay' => $stay,
                        'departure' => '',
                    ];

                    $table_data[] = [
                        'date' => $full_date_2->format('d.m.Y'),
                        'town' => $town,
                        'arrival' => '',
                        'stay' => '',
                        'departure' => $full_date_2->format('H:i'),
                    ];
                }
            }

            return (string)\View::make('mcmraak.rivercrs::infoflot_schedule', ['table' => $table_data]);
        }
        catch (Exception $ex) {
            $error = $ex->getMessage();
            JLog::add(
                'error',
                'infoflotDesignSchedule()',
                $error,
                'Ошибка при обработке расписания, cruise_id = '.$this->id);
            return '';
        }
    }

    # Заполнение цен
    function fillInfoflotPrices($cruise, $cabins, $checkin_id, $ship)
    {
        $types = $this->getCabinTypes($cabins);

        $del_q = [];
        $ins_q = [];
        foreach ($cruise['prices'] as $item) {
            $cabin_name = $item['name'];
            if($this->isCabinNotLet($cabin_name, $ship->id)) continue;
            $cabin_type = @$types[$cabin_name];
            if(!$cabin_type) continue;

            $deck = $this->getDeck($cabin_type['deck_name']);
            $price = $item['price'];

            $cabin = Cabin::where('category', $cabin_name)
                ->where('motorship_id', $ship->id)
                ->first();

            if(!$cabin) {
                $cabin = new Cabin;
                $cabin->motorship_id = $ship->id;
                $cabin->category = $cabin_name;
                $cabin->infoflot_name = $cabin_name;
                $cabin->places_main_count = $cabin_type['place_count'];
                $cabin->desc = '';
                $cabin->save();
            }

            $this->deckPivotCheck($cabin->id, $deck->id);

            $queryes = $this->updateCabinPrice($checkin_id, $cabin->id, (int) $price, null, 1);
            $del_q[] = $queryes['del'];
            $ins_q[] = $queryes['ins'];
        }

        $del_q = array_unique($del_q);
        $ins_q = array_unique($ins_q);
        $del_q = join(';', $del_q);
        $ins_prefix = 'INSERT INTO `mcmraak_rivercrs_pricing` (`checkin_id`, `cabin_id`, `price_a`, `price_b`, `desc`) VALUES ';
        $ins_q = $ins_prefix . join(',', $ins_q).';';
        DB::unprepared($del_q);
        DB::unprepared($ins_q);

    }

    function getCabinTypes($cabins)
    {
        $new_type = false;
        $types = [];
        foreach ($cabins as $cabin) {
            $type = $cabin['type'];
            if(!$new_type || ($type != $new_type)) {
                $types[$type] = [
                    'deck_name' => $cabin['deck_name'],
                    'place_count' => count($cabin['places'])
                ];
            }
        }
        return $types;
    }
}
