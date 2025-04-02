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
use Mcmraak\Rivercrs\Classes\SQS;
use October\Rain\Exception\ApplicationException;
use Mcmraak\Rivercrs\Classes\ParserLog;

trait InfoflotV2
{
    # http://azimut.dc/rivercrs/api/v2/parser/infoflotMount?id=535&debug
    function infoflotMount()
    {

        $page_id = Input::get('id');

        $debug = (Input::get('debug') !== null)?true:false;

        $idsDB = new Ids('infoflot_cache', CacheSettings::get('infoflot_tours'));

        if(!$page_id) {
            ParserLog::cleanErrors('infoflot');
            $ships = $this->cacheWarmUp('infoflot2-ships', 'array', ['page'=> 1, 'limit' => 1], 7, 0, 1);
            if(!isset($ships['pagination']['pages']['total'])) {
                $this->json(['repeat' => "Повтор метода infoflot2-ships page=1"]);
                return;
            }

            $pages_count = $ships['pagination']['pages']['total'];
            $pages_count++;
            $pages = [];
            for($i=1;$i<$pages_count;$i++) {
                $pages[] = $i;
            }
            if($debug) dd($pages);
            $pages = $idsDB->liveIds($pages,'ship_page:');
            $this->json(['ids' => $pages]);
            return;
        }

        $cache_id = 'ship_page:'.$page_id;

        if(!$debug) {
            $id_exist = $idsDB->testAndCreate($cache_id);
            if($id_exist) {
                $this->json(['html' => "Кеш уже обработан"]);
                return;
            }
        }

        $ship = $this->cacheWarmUp('infoflot2-ships', 'array', ['page'=> $page_id, 'limit' => 1], 7, 0, 1);

        if($debug) dd($ship);

        if(!isset($ship['data'])) {
            $idsDB->delete($cache_id);
            $this->json(['repeat' => "Повтор метода infoflot2-ships page=$page_id"]);
            return;
        }

        $ship_eds_id = $ship['data'][0]['id'];

        $ship_name = $ship['data'][0]['name'];

        if(CacheSettings::shipIsBad($ship_name, 'infoflot')) {
            $idsDB->addError($cache_id);
            $error_text = "Теплоход $ship_name пропущен из за исключения";
            ParserLog::saveError('infoflot', "infoflot2-ships/page=$page_id",$error_text);
            $this->json(['note' => $error_text]);
            return;
        }

        $idsDB->updateData($cache_id, ['ship_eds_id' => $ship_eds_id]);

        $this->json(['html' => "Кеш круизов теплохода #$ship_eds_id"]);
    }

    # http://azimut.dc/rivercrs/api/v2/parser/infoflotToursByShip
    # http://azimut.dc/rivercrs/api/v2/parser/infoflotToursByShip?id=1
    function infoflotToursByShip()
    {
        $ship_id = Input::get('id');
        $idsDB = new Ids('infoflot_cache', CacheSettings::get('infoflot_tours'));
        $debug = (Input::get('debug') !== null)?true:false;


        if(!$ship_id) {
            $ids_cache = collect($idsDB->like('ship_page:', 0))
                ->pluck('data')
                ->pluck('ship_eds_id')
                ->toArray();
            if($debug) dd($ids_cache);
            $ids_cache = $idsDB->liveIds($ids_cache,'tours_by_ship:');
            $this->json(['ids' => $ids_cache]);
            return;
        }

        $cache_id = 'tours_by_ship:'.$ship_id;

        if(!$debug) {
            $id_exist = $idsDB->test($cache_id);
            if($id_exist) {
                $this->json(['html' => "Кеш уже обработан"]);
                return;
            }
        }

        $tours = $this->cacheWarmUp('infoflot2-tours', 'array', ['ship' => $ship_id], 7, 0, 1);

        if(@$tours['status'] == 404) {
            $error_text = "Данные для корабля #$ship_id не предоставлены";
            $this->json(['note' => $error_text]);
            ParserLog::saveError('infoflot', "infoflot2-tours/ship=$ship_id",$error_text);
            $idsDB->addError($cache_id);
            return;
        }

        # Попытки
        if(!isset($tours['pagination']['pages']['total'])) {
            $idsDB->delete($cache_id);
            $this->json(['repeat' => "Повтор метода infoflot2-ships ship=$ship_id"]);
            return;
        }

        $pages_count = $tours['pagination']['pages']['total'];

        if(!$pages_count) {
            $idsDB->delete($cache_id);
            $error_text = "У коробля #$ship_id нет заездов";
            $this->json(['note' => $error_text]);
            ParserLog::saveError('infoflot', "infoflot2-tours/ship=$ship_id",$error_text);
            return;
        }

        $page = 1;
        while(true) {
            $arr = $this->cacheWarmUp('infoflot2-tours', 'array', ['page' => $page, 'ship' => $ship_id], 7, 0, 1);


            # Попытки
            if(!isset($arr['data'])) {
                $idsDB->delete($cache_id);
                $this->json(['repeat' => "Повтор метода infoflot2-ships ship=$ship_id"]);
                return;
            }

            $tours = $arr['data'];
            $ids_arr = [];
            foreach ($tours as $tour) {
                $ids_arr[] = $ship_id.'.'.$tour['id'];
            }
            $idsDB->addIds('ship_tour:', $ids_arr);
            if(!$pages_count || $page == $pages_count) break;
            $page++;
        }

        $idsDB->add($cache_id);
        $this->json(['html' => "Пакет ids круизов для ship#$ship_id"]);
    }



    # http://azimut.dc/rivercrs/api/v2/parser/infoflotToursById?debug
    # http://azimut.dc/rivercrs/api/v2/parser/infoflotToursById?id=4.8546&debug
    function infoflotToursById()
    {

        $idsDB = new Ids('infoflot_cache', CacheSettings::get('infoflot_tours'));

        $id = Input::get('id');
        $debug = (Input::get('debug') !== null)?true:false;

        if(!$id) {
            $ids = $idsDB->like('ship_tour:', 1);
            $ids = collect($ids)->keys()->toArray();
            $ids_clean = [];
            foreach ($ids as $item) {
                $ids_clean[] = explode('.', $item)[1];
            }

            $ids_clean = $idsDB->liveIds($ids_clean,'cruise:');

            if($debug) dd($ids_clean);

            $this->json(['ids' => $ids_clean]);
            return;
        }

        $cache_id = "cruise:$id";

        if(!$debug) {
            $id_exist = $idsDB->test($cache_id);
            if($id_exist) {
                $this->json(['html' => "Кеш уже обработан"]);
                return;
            }
        }

        if(strpos($id, '_bad') !== false) {
            $id = str_replace('_bad', '', $id);
            $idsDB->add($cache_id, 0, 1, 1);
            $error_text = "Заезд cruise#$id помечен как ошибочный и исключён из кеша";
            $this->json(['note' => $error_text]);
            ParserLog::saveError('infoflot', "infoflot2-tours/cruise#$id", $error_text);
            return;
        }

        $cruise = $this->cacheWarmUp('infoflot2-tours', 'array', ['id' => $id], 7, 0, 1);


        # Проверка на получение данных заезда
        if(!isset($cruise['dateStart']) || !isset($cruise['dateEnd'])) {
            $idsDB->delete($cache_id);
            $this->json(['repeat' => "Повтор метода infoflot2-tours cruise_id=$id"]);
            return;
        }

        $date_start = $cruise['dateStart'];

        //$date_start = Carbon::parse($date_start);
        //dd($date_start);

        $date_start = strtotime($date_start);

        # Заезд не актуален
        if($date_start < time()) {
            $error_text = "Заезд cruise#$id не актуален ({$cruise['dateStart']}) и исключён из кеша";
            $this->json(['note' => $error_text]);
            ParserLog::saveError('infoflot', "infoflot2-tours/cruise#$id",$error_text);
            $idsDB->add($cache_id, 0, 1, 1);
            #$idsDB->delete($cache_id);
            return;
        }


        $prices = $this->cacheWarmUp('infoflot2-cabins', 'array', ['id' => $id], 7, 0, 1);

        # Проверка на получение цен
        if(!isset($prices['prices'])) {
            $idsDB->delete($cache_id);
            $this->json(['repeat' => "Повтор метода infoflot2-cabins cruise_id=$id"]);
            return;
        }

        if($debug) {
            dd($cruise, $prices);
        }


        $idsDB->add($cache_id);

        $this->json(['html' => "Подготовлен пакет кеша круиза кают и цен для cruise#$id"]);

    }

    # http://azimut.dc/rivercrs/api/v2/parser/infoflotSeeder?id=init&debug=true
    function infoflotSeeder()
    {
        die('stop');
        $idsDB = new Ids('infoflot_cache', CacheSettings::get('infoflot_tours'));
        $id = Input::get('id');
        $debug = (Input::get('debug') !== null)?true:false;

        if($id == 'init') {
            $ids = $idsDB->like('cruise:', 1);
            $ids = collect($ids)->keys()->toArray();
            $ids_clean = [];
            foreach ($ids as $item) {
                $ids_clean[] = explode(':', $item)[1];
            }
            $ids_clean = $idsDB->liveIds($ids_clean,'prices:');

            if($debug) dd($ids_clean);
            $this->json($ids_clean);
            return;
        }

        $cruise = $this->cacheWarmUp('infoflot2-tours', 'array', ['id' => $id], 7, 0, 1);
        $prices = $this->cacheWarmUp('infoflot2-cabins', 'array', ['id' => $id], 7, 0, 1);

        dd($cruise, $prices);


        $ship_name = $cruise['ship']['name'];
        $ship_eds_id = $cruise['ship']['id'];

        $ship = $this->getMotorship($ship_name, 'infoflot_id', $ship_eds_id);
        $waybill = $this->getInfoflotWaybill($cruise);
        //$dates = $this->getInfoflotDates($route);

        $checkin = Checkin::where('eds_code', 'infoflot')
            ->where('eds_id', $id)
            ->first();


        if($checkin) {
            $checkin = new Checkin;
        }




    }

    # Получить маршрут
    function getInfoflotWaybill($cruise)
    {

        $route = $cruise['route'];
        $route = explode(' – ', $route);

        $waybill = [];
        $key = 0;
        $max = count($route) - 1;
        foreach ($route as $point) {
            $town_id = $this->getTownId($point, 'infoflot');
            $waybill[] = [
                'town' => $town_id,
                'excursion' => '',
                'bold' => ($key==0 || $key == $max)?1:0
            ];
            $key++;
        }

        return $waybill;
    }

    function getInfoflotDates($cruise)
    {

    }



}
