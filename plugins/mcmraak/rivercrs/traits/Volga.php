<?php namespace Mcmraak\Rivercrs\Traits;

use Mcmraak\Rivercrs\Classes\CacheSettings;
use Exception;
use Log;
use Mcmraak\Rivercrs\Models\Motorships as Ship;
use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use Mcmraak\Rivercrs\Models\Cabins as Cabin;
use Mcmraak\Rivercrs\Models\Pricing;
use DB;
use Input;
use Carbon\Carbon;
use Mcmraak\Rivercrs\Models\Log as JLog;

trait Volga
{
    public function volgaDecksSeeder()
    {
        $dump = $this->cacheWarmUp('volgawolga-database', 'array');
        $decks_dump = $dump['decks']['deck'];
        foreach ($decks_dump as $volga_deck)
        {
            $volga_name = $volga_deck['@attributes']['name'];
            $deck =  DB::table('mcmraak_rivercrs_decks')->where('name', 'like', "%$volga_name%")->first();
            if(!$deck) {
                DB::table('mcmraak_rivercrs_decks')->insert([
                    'name' => $volga_name.' палуба',
                    'sort_order' => 10
                ]);
            }
        }
    }

    public $id;

    public $volgaDump;

    public function volgaCabinsSeeder()
    {

        # http://azimut.dc/rivercrs/debug/Getter@volgaCabinsSeeder?debug_id=3117 // Разбор расписания
        # http://azimut.dc/rivercrs/debug/Getter@volgaCabinsSeeder?debug_id=3117

        $debug = false; #405;



        # ids collection
        $id = Input::get('id');

        if(Input::get('debug_id')) {
            $debug = Input::get('debug_id');
            $id = $debug;
        }


        if($this->testCruiseEx($id)) {
            $this->json(['message' => "Волга Wolga - Заезд #$id входит в исключения"]);
            return;
        }

        $dump = $this->volgaDump = $this->cacheWarmUp('volgawolga-database', 'array');
        //dd($dump['spos']['spo']);


        if(!isset($dump['cruises']['cruise'])){
            $this->json(['message' => 'Волга Wolga - Заездов не предоставлено']);
            return;
        }

        $cruises_dump = $dump['cruises']['cruise'];

        ## $id = 'init'; # DEBUG

        if($id == 'init') {
            $ids = [];

            foreach ($cruises_dump as $volga_cruise) {
                $volga_cruise_id = $volga_cruise['@attributes']['id'];
                $ids[] = $volga_cruise_id;
            }

            $this->json($ids);
            return;
        }

        //$id = '405'; # DEBUG

        if($debug) $id = $debug;

        if(!$debug)
        if($this->volgaId($id)) {
            $this->json([
                'message' => "Заезд:$id уже обработан"
            ]);
            return;
        };

        $this->id = $id;


        $volga_cruise = $this->getVolgaCruise($id, $cruises_dump);

        $checkin = ($debug)?false:Checkin::where('eds_code','volga')
            ->where('eds_id', $id)
            ->first();

        #$checkin = false; #DEBUG


        $gama_ship_name = $this->getVolgaShipName($volga_cruise['ship_id'], $dump);

        $motorship = $this->getMotorship($gama_ship_name, 'volga', $volga_cruise['ship_id']);


        try {

            if (!$checkin) {

                $waybill = $this->volgaWayBolder($volga_cruise);
                #dd($waybill);

                if(!$waybill) {
                    $error = print_r($volga_cruise, 1);
                    JLog::add('error', 'Volga@volgaCabinsSeeder', $error, 'Ошибка при обработке круиза [Отсутсвует маршрут]:'.$id);
                    $this->json([
                        'message' => "Заезд:$id уже обработан"
                    ]);
                    return;
                }

                $date = $volga_cruise['begin_date'];
                $date = date('Y-m-d', strtotime($date));
                $date .= ' ' . $volga_cruise['begin_time'];
                $dateb = $volga_cruise['end_date'];
                $dateb = date('Y-m-d', strtotime($dateb));
                $dateb .= ' ' . $volga_cruise['end_time'];
                $this->daysDiffCheck($this->diffInIncompliteDays($date, $dateb), $id);
                $checkin = new Checkin;
                $checkin->date = $date;
                $checkin->dateb = $dateb;
                $checkin->motorship_id = $motorship->id;
                $checkin->active = 1;
                $checkin->eds_code = 'volga';
                $checkin->eds_id = (int)$id;
                $checkin->waybill_id = $waybill;
                $checkin->save();
            } else {
                $checkin->waybill_id = $this->volgaWayBolder($volga_cruise);
                $checkin->save();
            }
        }

        catch (Exception $ex) {
            $error = $ex->getMessage();
            JLog::add('error', 'Volga@volgaCabinsSeeder', $error, 'Ошибка при обработке круиза:'.$id);
            $this->json([
                'message' => 'Ошибка при обработке круиза:'.$id
            ]);
            return;
        }

        $prices = [];
        foreach ($dump['prices']['price'] as $item)
        {
            #dd($item); // осталось пробросить cabin_id (не надо)

            $cruise_id = $item['@attributes']['cruise_id'];
            if($cruise_id == $id) {
                if($item['@attributes']['nofull']=='0')
                $prices[] = [
                    'class_id' => $item['@attributes']['class_id'],
                    'price_value' => $item['@attributes']['price'],
                    'price2_value' => $this->getSPO($cruise_id, $item['@attributes']['class_id'])
                ];
            }
        }

        foreach ($prices as $price)
        {

            $price_value = intval($price['price_value']);
            $price2_value = intval($price['price2_value']);

            $volga_cabin_class = $this->getVolgaCabinClass($price, $dump);
            if($this->isCabinNotLet($volga_cabin_class['cabin_name'], $motorship->id)) continue;
            ## dd($volga_cabin_class);
            ## @out: cabin_name, cabin_comment, places_main_count, places_extra_count
            $volga_deck_name = $this->getVolgaDeckName($price, $dump);

            #TODO:DEPRICATED
            //$deck =  DB::table('mcmraak_rivercrs_decks')->where('name', 'like', "%$volga_deck_name%")->first();

            $deck = $this->getDeck($volga_deck_name);

            $cabin = Cabin::where('volga_name', $volga_cabin_class['cabin_name'])
                ->where('motorship_id', $motorship->id)
                ->first();

            if(!$cabin)
            $cabin = Cabin::where('category', $volga_cabin_class['cabin_name'])
                ->where('motorship_id', $motorship->id)
                ->first();

            if(!$cabin) {
                $cabin = new Cabin;
                $cabin->motorship_id = $motorship->id;
                $cabin->category = $volga_cabin_class['cabin_name'];
                $cabin->places_main_count = $volga_cabin_class['places_main_count'];
                $cabin->places_extra_count = $volga_cabin_class['places_extra_count'];
                $cabin->volga_name = $volga_cabin_class['cabin_name'];
                $cabin->desc = $volga_cabin_class['cabin_comment'];
                $cabin->save();
            }

            if($deck) $this->deckPivotCheck($cabin->id, $deck->id);

            $this->updateCabinPrice($checkin->id, $cabin->id, $price_value, $price2_value);

        }

        # Проверка
        $this->testCheckin($id, 'volga');

        # Удаление кеша (В очередь)
        # app('\Mcmraak\Rivercrs\Controllers\Checkins')->recacheChekin($checkin->id);

        # Поисковый кеш
        //$checkin->updateSearchCache();

        # Complite and create progress-message
        $this->json([
            'message' => 'Обработка: '.$volga_cruise['route'].' Заезд #'.$id.': '.$motorship->name
        ]);

    }

    public function testCruiseEx($id)
    {
        # Тесты

        # Фильтр: 51##
        # Фильтр: 3##
        #$id = 5103; # true V
        #$id = 510;  # false V
        #$id = 5203; # false V
        #$id = 3133; # false V
        #$id = 313; # true V

        $ex_arr = CacheSettings::get('volga_ex_cruises');
        if(!$ex_arr) return false;
        $id_arr = str_split($id);
        $snap = false;
        foreach ($ex_arr as $item) {
            if($item['ex']==$id) return true;
            $str_arr = str_split($item['ex']);
            if(count($id_arr) != count($str_arr)) continue;
            $i = 0;
            $snap = true;
            foreach ($str_arr as $char) {
                if($char=='#' || $id_arr[$i] == $char) {
                    $i++;
                    continue;
                } else {
                    $snap = false;
                    break;
                }
            }
        }
        return $snap;
    }

    public function getSPO($cruise_id, $class_id)
    {
        if(!@$this->volgaDump['spos']['spo']) return null;

        foreach ($this->volgaDump['spos']['spo'] as $spo) {
            if($spo['@attributes']['cruise_id'] == $cruise_id && $spo['@attributes']['class_id'] == $class_id) {
                return $spo['@attributes']['spo'];
            }
        }
        return null;
    }

    public function getVolgaCabinClass($price, $dump)
    {
        foreach ($dump['classes']['class'] as $item)
        {
            if($item['@attributes']['id'] == $price['class_id']) {
                return [
                    'cabin_name' => $item['@attributes']['name'],
                    'cabin_comment' => $item['@attributes']['comment'],
                    'places_main_count' => $item['@attributes']['m_count'],
                    'places_extra_count' => $item['@attributes']['r_count'],
                ];
            }
        }
    }

    public function getVolgaDeckName($price, $dump)
    {
        $deck_id = false;
        foreach ($dump['cabins']['cabin'] as $item)
        {
            $class_id = $this->findAttr($item, 'class_id');
            if($class_id == $price['class_id']) {
                $deck_id = $this->findAttr($item, 'deck');
            }
        }

        if($deck_id)
        foreach ($dump['decks']['deck'] as $item)
        {
            $test_deck_id = $this->findAttr($item, 'id');
            if($test_deck_id == $deck_id) {
                return $this->findAttr($item, 'name');
            }
        }

//        $message = "volga: Проблема с обработкой палубы, заед#{$this->id}";
//        JLog::add('error', 'Getter@getVolgaDeckName', print_r([
//            'price' => $price,
//            'cruise_id' => $this->id
//        ], 1), $message);
//        $this->json([
//            'message' => $message
//        ]);
//        die;
        return false;
    }

    public function getVolgaShipName($ship_id, $dump)
    {
        foreach ($dump['ships']['ship'] as $ship) {
            $test_ship_id = $this->findAttr($ship, 'id');
            if($test_ship_id == $ship_id) {
                return $this->findAttr($ship, 'name');
            }
        }
        $message = "volga: Не найден теплоход #$ship_id";
        JLog::add('error', 'Getter@getVolgaShipName', $message, $message);
        $this->json([
            'message' => $message
        ]);
        die;
    }

    public function getVolgaCruise($id, $dump)
    {
        foreach ($dump as $item) {
            if($item['@attributes']['id'] == $id) {
                return $item['@attributes'];
            }
        }
        $message = "volga: Не найден круиз #$id";
        JLog::add('error', 'Getter@getVolgaCruise', $message, $message);
        $this->json([
            'message' => $message
        ]);
        die;
    }

    public function volgaWayBolder($volga_cruise)
    {
        $dump = $this->cacheWarmUp('volgawolga-database-short', 'array');
        $volga_cruise_id = $volga_cruise['id'];


        $short_way = null;
        foreach ($dump['cruises']['cruise'] as $cruise){
            $id = $cruise['@attributes']['id'];
            if($volga_cruise_id == $id) {
                $short_way = $cruise['@attributes']['route']; # $real_short_way убрать
            }
        }

        # dd(mb_strlen($string));

        #$test_route = $volga_cruise['route'];#DEBUG
        #file_put_contents("routes.txt", "$test_route\n",FILE_APPEND);#DEBUG

        $long_route = $this->checkSeparator($volga_cruise['route']);
        $short_way = $this->checkSeparator($short_way);

        $long_way = explode('-', $long_route);
        $short_way = explode('-', $short_way);

        #dd($long_way, $short_way);

        $bold = [];
        foreach ($short_way as $route){
            #file_put_contents("add_towns.txt", "$route: $test_route\n",FILE_APPEND);#DEBUG
            #if(!$route) { #DEBUG
                #file_put_contents("add_towns.txt", "[S][{$this->id}]: $real_short_way\n",FILE_APPEND); #DEBUG
            #} #DEBUG
            $bold[] = $this->getTownId($route, 'volga');
        }

        $waybill = [];
        $key = 0;
        $last = count($long_way) - 1;
        foreach ($long_way as $route){
            //file_put_contents("add_towns.txt", "{$this->id} :$route: $test_route\n",FILE_APPEND);#DEBUG

            #if(!$route) { #DEBUG
                #file_put_contents("add_towns.txt", "[L][{$this->id}]: {$volga_cruise['route']}\n",FILE_APPEND); #DEBUG
            #} #DEBUG

            $town_id = $this->getTownId($route, 'volga');
            $waybill[] = [
                'town' => $town_id,
                'excursion' => '',
                'bold' => (in_array($town_id, $bold) || $key==0 || $key==$last)?1:0,
            ];
            $key++;
        }
        return $waybill;
    }

}
