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
use October\Rain\Exception\ApplicationException;
use Mcmraak\Rivercrs\Classes\ParserLog;

trait InfoflotV3
{
    # Метод 1 - Перебор теплоходов
    # http://azimut.dc/rivercrs/api/v2/parser/infoflotShips
    function infoflotShips()
    {
        $page_id = Input::get('id');
        $debug = (Input::get('debug') !== null)?true:false;
        $idsDB = new Ids('infoflot_cache', CacheSettings::get('infoflot_tours'));

        if(!$page_id) {
            #ParserLog::cleanErrors('infoflot');
            $shipsAnswer = $this->cacheWarmUp('infoflot-ships', 'array', ['page'=> 1, 'limit' => 1], 13, 0, 1, 1);
            $ships = $shipsAnswer['answer'];
            $api_url = $shipsAnswer['url'];
            if(!isset($ships['pagination']['pages']['total'])) {
                $this->json(['repeat' => "Повтор метода infoflot-ships page=1"]);
                JLog::addLog([
                    'title' => 'Повтор запроса',
                    'method' => 'InfoflotV3@infoflotShips',
                    'type' => 'error',
                    'url' => $api_url,
                    'eds_code' => 'infoflot',
                    'text' => 'Запрос не вернул данные'
                ]);
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

        if(strpos($page_id, '_bad') !== false) {
            $page_id = str_replace('_bad', '', $page_id);

            //$idsDB->add("ship_page:$page_id", 1, 1, 1);
            $idsDB->delete("ship_page:$page_id");
            $error_text = "Запрос к кораблю page#$page_id помечен как ошибочный и исключён, причина: Сервер не вернул данные";
            $this->json(['note' => $error_text]);
            #ParserLog::saveError('infoflot', "infoflot-ships/page#$page_id", $error_text);
            JLog::addLog([
                'title' => 'Прекращение попыток',
                'method' => 'InfoflotV3@infoflotShips',
                'type' => 'error',
                'url' => '',
                'eds_code' => 'infoflot',
                'text' => 'Данный запрос будет пропущен'
            ]);
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

        $shipAnswer = $this->cacheWarmUp('infoflot-ships', 'array', ['page'=> $page_id, 'limit' => 1], 13, 0, 1, 1);
        $ship = $shipAnswer['answer'];
        $api_url = $shipAnswer['url'];
        if($debug) dd($ship);

        if(!isset($ship['data'])) {
            $idsDB->delete($cache_id);
            $this->json(['repeat' => "Повтор метода infoflot-ships page=$page_id"]);
            JLog::addLog([
                'title' => 'Повтор запроса',
                'method' => 'InfoflotV3@infoflotShips',
                'type' => 'error',
                'url' => $api_url,
                'eds_code' => 'infoflot',
                'text' => 'Запрос не вернул данные'
            ]);
            return;
        }

        $ship_eds_id = $ship['data'][0]['id'];

        $ship_name = $ship['data'][0]['name'];

        if(CacheSettings::shipIsBad($ship_name, 'infoflot')) {
            $idsDB->addError($cache_id);
            $error_text = "Теплоход \"$ship_name\" пропущен и помечен как ошибочный, причина: Теплоход найден в исключениях для данного источника";
            #ParserLog::saveError('infoflot', "infoflot-ships/page=$page_id",$error_text);
            JLog::addLog([
                'title' => 'Исключение',
                'method' => 'InfoflotV3@infoflotShips',
                'type' => 'note',
                'url' => $api_url,
                'eds_code' => 'infoflot',
                'text' => "Теплоход \"$ship_name\" пропущен т.к. указан в исключениях"
            ]);
            $this->json(['note' => $error_text]);
            return;
        }

        $idsDB->add("valid_ship:$ship_eds_id");
        $this->json(['html' => "Кеш круизов теплохода #$ship_eds_id"]);
    }


    # Метод 2 - Перебор круизов и цен
    # http://azimut.dc/rivercrs/api/v2/parser/infoflotCruisesCache
    function infoflotCruisesCache()
    {
        $id = Input::get('id');
        $debug = (Input::get('debug') !== null)?true:false;
        $idsDB = new Ids('infoflot_cache', CacheSettings::get('infoflot_tours'));

        if(!$id) {
            $valid_ships = $idsDB->like('valid_ship', 0);
            $valid_ships = collect(array_keys($valid_ships))->map(function ($item){
                return explode(':', $item)[1];
            })->toArray();
            $valid_ships = join(',', $valid_ships);
            $query_opts = [
                'ship' => $valid_ships,
                'date' => date('Y-m-d'), // 2020-07-20 #TODO: Было date('Y-m-d')
                'limit' => 1
            ];

            Cache::forever('infoflot-cruises:query_opts', $query_opts);

            $cruisesAnswer = $this->cacheWarmUp('infoflot-cruises', 'array', $query_opts, 13, 0, 1, 1);
            $cruises = $cruisesAnswer['answer'];
            $api_url = $cruisesAnswer['url'];
            # Попытки
            if(!isset($cruises['pagination']['pages']['total'])) {
                $this->json(['repeat' => "Повтор метода infoflot-cruises mount"]);
                JLog::addLog([
                    'title' => 'Повтор запроса',
                    'method' => 'InfoflotV3@infoflotCruisesCache',
                    'type' => 'error',
                    'url' => $api_url,
                    'eds_code' => 'infoflot',
                    'text' => 'Запрос не вернул данные'
                ]);
                return;
            }

            $ids = range(1, $cruises['pagination']['pages']['total']);
            $ids = $idsDB->liveIds($ids, 'cruise_page:');
            $this->json(['ids' => $ids]);
            return;
        }


        if(strpos($id, '_bad') !== false) {
            $id = str_replace('_bad', '', $id);
            $idsDB->add("cruise_page:$id", 1, 1, 1);
            $error_text = "Заезд cruise#$id помечен как ошибочный и исключён, причина: Сервер не вернул данные.";
            $this->json(['note' => $error_text]);
            JLog::addLog([
                'title' => 'Прекращение попыток',
                'method' => 'InfoflotV3@infoflotCruisesCache',
                'type' => 'error',
                'url' => '',
                'eds_code' => 'infoflot',
                'text' => 'Данный запрос будет пропущен'
            ]);
            #ParserLog::saveError('infoflot', "infoflot-tours/cruise#$id", $error_text);
            return;
        }


        $cache_id = "cruise_page:$id";

        if(!$debug) {
            $id_exist = $idsDB->test($cache_id);
            if($id_exist) {
                $this->json(['html' => "Кеш уже обработан"]);
                return;
            }
        }

        $query_opts = Cache::get('infoflot-cruises:query_opts');
        $query_opts['page'] = $id;

        $cruiseAnswer = $this->cacheWarmUp('infoflot-cruises', 'array', $query_opts, 13, 0, 1, 1);
        $cruise = $cruiseAnswer['answer'];
        $api_url = $cruiseAnswer['url'];


        # Попытки
        if(!isset($cruise['data'][0]['id'])) {
            $idsDB->delete($cache_id);
            $this->json(['repeat' => "Повтор метода infoflot-cruises page=$id"]);
            $error_text = "Ошибка запроса: $api_url";
            #ParserLog::saveError('infoflot', "infoflot-cruises/page#$id",$error_text);
            JLog::addLog([
                'title' => 'Повтор запроса',
                'method' => 'InfoflotV3@infoflotCruisesCache',
                'type' => 'error',
                'url' => $api_url,
                'eds_code' => 'infoflot',
                'text' => 'Запрос не вернул данные $cruise[\'data\'][0][\'id\']'
            ]);
            return;
        }

        $cruise = $cruise['data'][0];

        $date_start = $cruise['dateStart'];
        $date_start = strtotime($date_start);
        # Заезд не актуален

        # TODO: Вернуть эту проверку

        if($date_start < time()) {
            $error_text = "Заезд cruise#$id не актуален ({$cruise['dateStart']}) и исключён из кеша";
            $this->json(['note' => $error_text]);
            #ParserLog::saveError('infoflot', "infoflot-cruises/cruise#$id",$error_text);
            JLog::addLog([
                'title' => 'Условное исключение',
                'method' => 'InfoflotV3@infoflotCruisesCache',
                'type' => 'note',
                'url' => $api_url,
                'eds_code' => 'infoflot',
                'text' => $error_text
            ]);
            $idsDB->add($cache_id, 0, 1, 1);
            #$idsDB->delete($cache_id);
            return;
        }

        $cruise_id = $cruise['id'];
        $pricesAnswer = $this->cacheWarmUp('infoflot-cabins', 'array', ['id' => $cruise_id], 13, 0, 1, 1);
        $prices = $pricesAnswer['answer'];
        $api_url = $pricesAnswer['url'];

        # Проверка на получение информации
        if(!isset($prices['cruise'][0]['id'])) {
            $error_text = "Ошибка запроса: $api_url";
            #ParserLog::saveError('infoflot', "infoflot-cabins/cruise_id=$id", $error_text);
            $idsDB->delete($cache_id);
            $this->json(['repeat' => "Повтор метода infoflot-cabins cruise_id=$id"]);
            JLog::addLog([
                'title' => 'Повтор запроса',
                'method' => 'InfoflotV3@infoflotCruisesCache',
                'type' => 'error',
                'url' => $api_url,
                'eds_code' => 'infoflot',
                'text' => 'Запрос не вернул данные'
            ]);
            return;
        }

        # Проверка на наличие цен
        if(!isset($prices['prices'])) {
            $idsDB->add($cache_id, 1, 1, 1);
            $error_text = "Заезд cruise#$cruise_id не содержит цены и будет пропущен";
            $this->json(['note' => $error_text]);
            JLog::addLog([
                'title' => 'Условное исключение',
                'method' => 'InfoflotV3@infoflotCruisesCache',
                'type' => 'error',
                'url' => $api_url,
                'eds_code' => 'infoflot',
                'text' => $error_text
            ]);
            #ParserLog::saveError('infoflot', "infoflot-cabins/cruise#$cruise_id", $error_text);
            return;
        }

        $idsDB->add($cache_id);
        $this->json(['html' => "Подготовлен пакет кеша круиза кают и цен для cruise#$cruise_id"]);
    }

    # Преобразовать дату в формат MySQL
    function sqlDate($date_string)
    {
        preg_match('/(\d+-\d+-\d+)/', $date_string, $matches);
        $Ymd = $matches[0];
        preg_match('/(\d+:\d+:\d+)/', $date_string, $matches);
        $His = $matches[0];
        return "$Ymd $His";
    }

    # Получить маршрут
    function getInfoflotWaybill($cruise)
    {

        $route = $cruise['route'];
        $route = explode(' – ', $route);

        if(isset($cruise['routeShort'])) {
            $route_short = $cruise['routeShort'];
            $route_short = explode(' – ', $route_short);
        } else {
            $route_short = [];
        }


        $waybill = [];
        $key = 0;
        $max = count($route) - 1;
        foreach ($route as $point) {
            $town_id = $this->getTownId($point, 'infoflot');
            $waybill[] = [
                'town' => $town_id,
                'excursion' => '',
                'bold' => ($key==0 || $key == $max || in_array($point, $route_short))?1:0
            ];
            $key++;
        }

        return $waybill;
    }

    # Заполнить кабины и цены
    function fillInfoflotPrices($prices, $checkin, $ship)
    {
        $cabins = [];
        foreach ($prices['prices'] as $type_id => $price) {
            foreach ($prices['cabins'] as $cabin) {
                if($cabin['type_id'] == $type_id) {
                    $cabins[$price['type_name']] = [
                        'deck_id' => $this->getDeck($cabin['deck'])->id,
                        'places_main_count' => count($cabin['places']),
                        'price' => $price['prices']['main_bottom']['adult'],
                        #'desc' => $price['type_description']
                    ];
                    continue;
                }
            }
        }

        $del_q = [];
        $ins_q = [];
        foreach ($cabins as $cabin_name => $cabinData) {

            $cabin = Cabin::where('infoflot_name', $cabin_name)
                ->where('motorship_id', $ship->id)
                ->first();

            if(!$cabin)
                $cabin = Cabin::where('category', $cabin_name)
                    ->where('motorship_id', $ship->id)
                    ->first();

            if(!$cabin) {
                $cabin = new Cabin;
            }

            $cabin->motorship_id = $ship->id;
            $cabin->category = $cabin_name;
            $cabin->infoflot_name = $cabin_name;
            #$cabin->desc = $cabinData['desc'];
            $cabin->places_main_count = $cabinData['places_main_count'];
            $cabin->save();

            $this->deckPivotCheck($cabin->id, $cabinData['deck_id']);
            $queryes = $this->updateCabinPrice($checkin->id, $cabin->id, (int) $cabinData['price'], null, 1);
            $del_q[] = $queryes['del'];
            $ins_q[] = $queryes['ins'];
        }

        $this->updateCabinPriceQueries($del_q, $ins_q);
    }

    # http://azimut.dc/rivercrs/api/v2/parser/infoflotSeeder?id=init&debug=true
    function infoflotSeeder()
    {
        $idsDB = new Ids('infoflot_cache', CacheSettings::get('infoflot_tours'));
        $id = Input::get('id');
        $debug = (Input::get('debug') !== null)?true:false;

        if($id == 'init') {
            $ids = $idsDB->like('cruise_page:', 0);
            $ids = collect($ids)->keys()->map(function ($item){
                return explode(':', $item)[1];
            })->toArray();

            $ids = $idsDB->liveIds($ids,'cruise_seed:');

            if($debug) dd($ids);
            $this->json($ids);
            return;
        }

        try {

            $cache_id = "cruise_seed:$id";

            /*
            if(!$debug)
                if($this->infoflotId($id)) {
                    $this->json([
                        'message' => "Заезд:$id уже обработан"
                    ]);
                    return;
            };
            */

            if(!$debug) {
                $id_exist = $idsDB->test($cache_id);
                if($id_exist) {
                    $this->json([
                        'message' => "Заезд:$id уже обработан"
                    ]);
                    return;
                }
            }

            $query_opts = Cache::get('infoflot-cruises:query_opts');
            $query_opts['page'] = $id;

            $cruise = $this->cacheWarmUp('infoflot-cruises', 'array', $query_opts, 13, 0, 0);
            $cruise_id = $cruise['data'][0]['id'];
            $prices = $this->cacheWarmUp('infoflot-cabins', 'array', ['id' => $cruise_id], 13, 0, 0);

            $cruise = $cruise['data'][0];
            $infoflot_ship = $cruise['ship'];

            # Инициализация основных дданных
            $ship = $this->getMotorship($infoflot_ship['name'], 'infoflot_id', $infoflot_ship['id']);

            $waybill = $this->getInfoflotWaybill($cruise);
            $dateStart = $this->sqlDate($cruise['dateStart']);
            $dateEnd = $this->sqlDate($cruise['dateEnd']);

            # Создание заезда
            $checkin = Checkin::where('eds_code', 'infoflot')
                ->where('eds_id', $cruise_id)
                ->first();

            if (!$checkin) {
                $checkin = new Checkin;
            }

            $checkin->date = $dateStart;
            $checkin->dateb = $dateEnd;
            $checkin->desc_1 = '';
            $checkin->motorship_id = $ship->id;
            $checkin->active = 1;
            $checkin->eds_code = 'infoflot';
            $checkin->eds_id = (int)$cruise_id;
            $checkin->waybill_id = $waybill;
            $checkin->save();

            # Заполнение цен
            $this->fillInfoflotPrices($prices, $checkin, $ship);

            # Проверка
            $this->testCheckin($id, 'infoflot');

            # Поисковый кеш
            //$checkin->updateSearchCache();

            $idsDB->delete("cruise_page:$id");

            $this->json([
                'message' => "Обработка: Заезд #$cruise_id [checkin_id:{$checkin->id}]"
            ]);

            $idsDB->add($cache_id);

        } catch (Exception $ex) {
            $error = $ex->getMessage();
            #ParserLog::saveError('infoflot', "infoflotSeeder/parse_id:$id", 'Ошибка: '.$error);
            JLog::addLog([
                'title' => 'Ошибка',
                'method' => 'InfoflotV3@infoflotSeeder',
                'type' => 'error',
                'url' => "/rivercrs/api/v2/parser/infoflotSeeder?id=$id",
                'eds_code' => 'infoflot',
                'text' => $error
            ]);
            $this->json([
                'message' => 'Ошибка при обработке круиза: page:'.$id.', Ошибка: '.$error
            ]);
            return;
        }

    }
}
