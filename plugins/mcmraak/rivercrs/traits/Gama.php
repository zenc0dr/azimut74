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

trait Gama {

    public $id;

    # Гама - Кеш круизов
    # http://azimut.dc/rivercrs/api/v2/parser/gamaCruisesCache
    # http://azimut.dc/rivercrs/api/v2/parser/gamaCruisesCache?id=17438
    public function gamaCruisesCache()
    {
        $id = Input::get('id');

        if(!$id) {
            $dump = $this->get('xml',
                'https://gama-nn.ru/execute/navigation',
                null,
                //CacheSettings::get('gama_cruises')
                0
            );

            $navigations = $dump['navigations']['navigation'];
            $ids = [];
            foreach ($navigations as $navigation){

                if(!isset($navigation['ways']['way'])) {
                   continue;
                }

                foreach ($navigation['ways']['way'] as $way) {
                    try {
                        if(isset($way['@attributes']['iid'])) {
                            $ids[] = (int) $way['@attributes']['iid'];
                        } elseif (isset($way['iid'])) {
                            $ids[] = (int) $way['iid'];
                        }
                    }
                    catch (Exception $ex) {
                        JLog::add('error', 'Gama@gamaCruisesIdsForCruises', print_r($way, 1));
                    }
                }
            }

            $url = 'https://gama-nn.ru/execute/way/';
            $cache_time = CacheSettings::get('gama_cruise');
            $live_ids = [];
            foreach ($ids as $id){
                if(!$this->testTimeCache($url.$id, $cache_time)){
                    $live_ids[] = $id;
                }
            }
            $this->json(['ids' => $live_ids]);
            return;
        }

        $error = $this->cacheWarmUp('gama-cruise', 'check', ['id' => $id], 10);

        if($error) {
            $this->json(['error' => $error]);
        } else {
            $this->json(['html' => "Детальный маршрут круиза #$id"]);
        }
    }

    # Гама - Кеш палуб
    # http://azimut.dc/rivercrs/api/v2/parser/gamaDecksCache
    # http://azimut.dc/rivercrs/api/v2/parser/gamaDecksCache?id=8
    public function gamaDecksCache()
    {
        $id = Input::get('id');

        if(!$id) {
            $ships = $this->get('xml',
                'https://api.gama-nn.ru/execute/view/ships',
                null,
                CacheSettings::get('gama_ships')
            );

            $cache_time = CacheSettings::get('gama_deck');

            $url = 'https://api.gama-nn.ru/execute/view/deck/';
            $live_ids = [];
            foreach ($ships['ships']['ship'] as $ship) {
                $id = $ship['@attributes']['iid'];
                if(!$this->testTimeCache($url.$id, $cache_time)){
                    $live_ids[] = (int) $id;
                }
            }

            $this->json(['isd' => $live_ids]);
            return;
        }

        $error = $this->cacheWarmUp('gama-deck', 'check', ['id' => $id], 10);

        if($error) {
            $this->json(['error' => $error]);
        } else {
            $this->json(['html' => "Палуба #$id"]);
        }

    }

    public function gamaSeeder()
    {
        try {
            $debug = false; // 16520

            # http://azimut.dc/rivercrs/debug/Getter@gamaSeeder

            $id = Input::get('id');

            $cruises = $this->cacheWarmUp('gama-cruises', 'array');
            $cruises = $cruises['navigations']['navigation'];

            ## $id = 'init'; # DEBUG
            if ($id == 'init') {
                $ids = [];
                foreach ($cruises as $cruise) {
                    $gama_ship_id = $cruise['@attributes']['ship_iid'];

                    if (!isset($cruise['ways']['way'])) {
                        continue;
                    }

                    foreach ($cruise['ways']['way'] as $way) {
                        $eds_id = false;
                        if (isset($way['@attributes']['iid'])) {
                            $eds_id = $way['@attributes']['iid'];
                        } else {
                            $eds_id = $way['iid'];
                        }

                        if (!$eds_id) dd('error');

                        $ids[] = $eds_id;
                    }
                }
                $this->json($ids);
                return;
            }

            $this->id = $id;

            if ($debug) $id = $debug;

            if (!$debug)
                if ($this->gamaId($id)) {
                    $this->json([
                        'message' => "Заезд:$id уже обработан"
                    ]);
                    return;
                };

            $cruise_arr = $this->getGamaCruise($id, $cruises);
            $gama_ship_id = $cruise_arr['gama_ship_id'];
            $gama_ship_name = $cruise_arr['gama_ship_name'];
            $way = $cruise_arr['way'];
            $date = $way['@attributes']['STS'];
            $dateb = $way['@attributes']['FTS'];
            $status_way = $way['@attributes']['Way'];
            $gama_cruise = $this->cacheWarmUp('gama-cruise', 'array', ['id' => $id]);

            # $gama_waybill = explode(' - ', $gama_waybill);

            # date
            # dateb
            # days
            # motorship_id
            # eds_code
            # eds_id

            if (!$debug)
                $checkin = Checkin::where('eds_code', 'gama')
                    ->where('eds_id', $id)
                    ->first();

            if ($debug) $checkin = false;

            if (!$checkin) {
                $ship = $this->getMotorship($gama_ship_name, 'gama_id', $gama_ship_id);

                $waybill = $this->gamaRouteBolder($status_way, $gama_cruise);
                $desc_1 = $this->gamaDesignSchedule($gama_cruise);

                $this->daysDiffCheck($this->diffInIncompliteDays($date, $dateb), $id);

                $checkin = new Checkin;
                $checkin->date = $date;
                $checkin->dateb = $dateb;

                $checkin->desc_1 = $desc_1;
                $checkin->motorship_id = $ship->id;
                $checkin->active = 1;
                $checkin->eds_code = 'gama';
                $checkin->eds_id = (int)$id;
                $checkin->waybill_id = $waybill;
                $checkin->save();
            } else {
                ### UPDATE WAYBILLS ###
                $updated_waybill = $this->gamaRouteBolder($status_way, $gama_cruise);
                $checkin->waybill_id = $updated_waybill;
                #######################
                ## Обновление расписаний ##
                $checkin->desc_1 = $this->gamaDesignSchedule($gama_cruise);
                $checkin->save();
                #######################
            }

            $gama_cabins = false;
            if (isset($gama_cruise['cabins']['cabin'])) {
                $gama_cabins = $gama_cruise['cabins']['cabin'];
                if (isset($gama_cabins['@attributes'])) {
                    $gama_cabins = [$gama_cabins];
                }
            }

            # На этом всё, Гама даёт цены только при проверке статуса в реальном времени


            if ($gama_cabins) {

                foreach ($gama_cabins as $gama_cabin) {

                    $gama_cabin_name = false;

                    $gama_cabin_name = $this->getGamaParam($gama_cabin, 'category_name');
                    $gama_cabin_id = $this->getGamaParam($gama_cabin, 'category_iid');
                    $gama_cabin_name .= '|' . $gama_cabin_id;

                    if ($this->isCabinNotLet($gama_cabin_name, $checkin->motorship_id)) continue;

                    if (!$gama_cabin_name) {
                        JLog::add('error',
                            'Gama@gamaSeeder',
                            'Отсутствуют кабина: ' . print_r($gama_cabin, 1), "Заезд:$id Отсутствует кабина");
                        continue;
                    }

                    # TODO: Этот метод используется для определения правильной цены
                    # Сравниваем с inCabin
                    # Цену брать std3
                    # $places = $this->getGamaParam($gama_cabin, 'places');

                    $cabin = Cabin::where('motorship_id', $checkin->motorship->id)
                        ->where('gama_name', $gama_cabin_name)->first();

                    if (!$cabin)
                        $cabin = Cabin::where('motorship_id', $checkin->motorship->id)
                            ->where('category', $gama_cabin_name)->first();


                    $gama_deck_id = $this->getGamaParam($gama_cabin, 'deck');

                    if (!$gama_deck_id) {
                        JLog::add('error',
                            'Gama@gamaSeeder',
                            'Отсутствуют палуба: ' . print_r($gama_cabin, 1), "Заезд:$eds_id Отсутствует палуба");
                        continue;
                    }

                    $deck = $this->getGamaDeck($gama_deck_id);

                    if (!$cabin) {
                        $cabin = new Cabin;
                        $cabin->motorship_id = $checkin->motorship->id;
                        $cabin->category = $gama_cabin_name;
                        $cabin->gama_name = $gama_cabin_name;
                        $cabin->desc = '';
                        $cabin->save();
                    }

                    $this->deckPivotCheck($cabin->id, $deck->id);
                }
            }

            # Тут нет обработки цен, так задумано.

            # Проверка
            $this->testCheckin($id, 'gama');

            # Удаление кеша (В очередь)
            # app('\Mcmraak\Rivercrs\Controllers\Checkins')->recacheChekin($checkin->id);

            # Поисковый кеш
            //$checkin->updateSearchCache();


            # Complite and create progress-message
            $this->json([
                'message' => 'Обработка: ' . $gama_ship_name . ' Заезд #' . $id . ': ' . $status_way
            ]);
        }
        catch (Exception $ex) {
            $error = $ex->getMessage();
            JLog::add('error', 'Gama@gamaSeeder', $error, 'Ошибка при обработке круиза:'.$this->id);
            $this->json([
                'message' => 'Ошибка при обработке круиза:'.$this->id
            ]);
            return;
        }
    }

    # Оформление таблицы расписания в заезде
    public function gamaDesignSchedule($gama_cruise)
    {
        try
        {

            $gama_cruise_route = [];
            foreach ($gama_cruise['path']['point'] as $route) {



                $town_name = $route['@attributes']['town_name'];
                $start_time = $route['@attributes']['STS'];
                $start_time = date('d.m.Y H:i:s', strtotime($start_time));
                $end_time = $route['@attributes']['ETS'];
                $end_time = date('d.m.Y H:i:s', strtotime($end_time));

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
            return (string)\View::make('mcmraak.rivercrs::gama_schedule', ['table' => $table_data]);
        }
        catch (Exception $ex) {
            $error = $ex->getMessage();
            JLog::add(
                'error',
                'Gama@gamaDesignSchedule',
                $error,
                'Ошибка при обработке расписания, cruise_id = '.$this->id);
            return '';
        }
    }


    public function gamaRouteBolder($bold, $cruise)
    {
        $bold = explode(' - ', $bold);
        $way = $cruise['path']['point'];

        $waybill = [];

        $i = 0;
        $ii = count($way) - 1;

        foreach ($way as $point)
        {
            $town_name = $point['@attributes']['town_name'];
            $town_id = $this->getTownId($town_name, 'gama');
            $waybill[] = [
                'town' => $town_id,
                'excursion' => '',
                'bold' => (in_array($town_name, $bold)||!$i||$i==$ii)?1:0
            ];
            $i++;
        }

        return $waybill;
    }

    public function getGamaParam($arr, $param_name){
        if (isset($arr['@attributes'][$param_name])) {
            return trim($arr['@attributes'][$param_name]);
        }
        if (isset($arr[$param_name])) {
            return trim($arr[$param_name]);
        }
        return false;
    }


    public function getGamaCruise($id, $cruises)
    {
        foreach ($cruises as $cruise) {

            if(!isset($cruise['ways']['way'])) {
                continue;
            }

            foreach ($cruise['ways']['way'] as $way) {

                $eds_id = $this->getGamaParam($way, 'iid');

                if ($eds_id == $id) {
                    return [
                        'gama_ship_id' => $this->getGamaParam($cruise, 'ship_iid'),
                        'gama_ship_name' => $this->getGamaParam($cruise, 'ship_name'),
                        'way' => $way,
                    ];
                };
            }
        }
    }

    public function getGamaDeck($gama_deck_id)
    {
        $gama_deck = $this->cacheWarmUp('gama-deck', 'array', ['id' => $gama_deck_id]);
        $gama_deck = $gama_deck['deck'];
        $gama_deck_name = $this->getGamaParam($gama_deck, 'name');

        return $this->getDeck($gama_deck_name);

        /*TODO:DEPRICATED
        $deck = Deck::where('name', 'like', "%$gama_deck_name%")->first();
        if(!$deck) {
            $deck = new Deck();
            $deck->name = $gama_deck_name;
            $deck->sort_order = 10;
            $deck->save();
        }
        return $deck;
        */
    }

}
