<?php namespace Mcmraak\Rivercrs\Traits;

ini_set('max_execution_time', '300');

use Mcmraak\Rivercrs\Classes\CacheSettings;
use Exception;
use Log;
use Mcmraak\Rivercrs\Models\Log as JLog;
use Input;
use Carbon\Carbon;
use Cache;

use DB;
use Mcmraak\Rivercrs\Models\Motorships as Ship;
use Mcmraak\Rivercrs\Models\Cabins as Cabin;
use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use Mcmraak\Rivercrs\Models\Pricing;
use Mcmraak\Rivercrs\Models\Towns as Town;
use Mcmraak\Rivercrs\Models\Decks as Deck;

trait Germes {

    # Гермес кеш статусов
    # http://azimut.dc/rivercrs/api/v2/parser/germesStatusCache
    # http://azimut.dc/rivercrs/api/v2/parser/germesStatusCache?id=14591
    public function germesStatusCache()
    {

        $id = Input::get('id');
            if(!$id) {
            $cruises = $this->get('xml',
                'https://river.sputnik-germes.ru/XML/exportTur.php',
                null,
                CacheSettings::get('germes_cruises')
            );

            $cache_time = CacheSettings::get('germes_status');

            //dd($cruises['тур'][0]['@attributes']['id']);

            $url = 'https://river.sputnik-germes.ru/XML/exportKauta.php?tur=';
            $live_ids = [];
            foreach ($cruises['тур'] as $cruise) {
                $id = $cruise['@attributes']['id'];
                if(!$this->testTimeCache($url.$id, $cache_time)){
                    $live_ids[] = (int) $id;
                }
            }

            $this->json(['ids' => $live_ids]);
            return;
        }

        $error = $this->cacheWarmUp('germes-status', 'check', ['id' => $id], 10);

        if($error) {
            $this->json(['error' => $error]);
        } else {
            $this->json(['html' => "Детальный маршрут круиза #$id"]);
        }
    }

    # Гермес кеш маршрутов
    # http://azimut.dc/rivercrs/api/v2/parser/germesTraceCache
    # http://azimut.dc/rivercrs/api/v2/parser/germesTraceCache?id=14624
    public function germesTraceCache()
    {
        $id = Input::get('id');
            if(!$id) {
            $cruises = $this->get('xml',
                'https://river.sputnik-germes.ru/XML/exportTur.php',
                null,
                CacheSettings::get('germes_cruises')
            );
            $cache_time = CacheSettings::get('germes_trace');
            $url = 'https://river.sputnik-germes.ru/XML/exportTrace.php?tur=';
            $live_ids = [];
            foreach ($cruises['тур'] as $cruise) {
                $id = $cruise['@attributes']['id'];
                if(!$this->testTimeCache($url.$id, $cache_time)){
                    $live_ids[] = (int) $id;
                }
            }
            $this->json(['ids' => $live_ids]);
            return;
        }

        $error = $this->cacheWarmUp('germes-trace', 'check', ['id' => $id], 10);

        if($error) {
            $this->json(['error' => $error]);
        } else {
            $this->json(['html' => "Детальный маршрут круиза #$id"]);
        }
    }

    # Гермес кеш экскурсий
    # http://azimut.dc/rivercrs/api/v2/parser/germesExcursionCache
    # http://azimut.dc/rivercrs/api/v2/parser/germesExcursionCache?id=14591
    public function germesExcursionCache()
    {
        $id = Input::get('id');
        if(!$id) {
            $cruises = $this->get('xml',
                'https://river.sputnik-germes.ru/XML/exportTur.php',
                null,
                CacheSettings::get('germes_cruises')
            );

            $cache_time = CacheSettings::get('germes_excursion');

            //dd($cruises['тур'][0]['@attributes']['id']);

            $url = 'https://river.sputnik-germes.ru/XML/exportExcursion.php?tur=';
            $live_ids = [];
            foreach ($cruises['тур'] as $cruise) {
                $id = $cruise['@attributes']['id'];
                if(!$this->testTimeCache($url.$id, $cache_time)){
                    $live_ids[] = (int) $id;
                }
            }

            $this->json(['ids' => $live_ids]);
            return;
        }

        $error = $this->cacheWarmUp('germes-excursion', 'check', ['id' => $id], 10);

        if($error) {
            $this->json(['error' => $error]);
        } else {
            $this->json(['html' => "Экскурсия круиза #$id"]);
        }
    }

    # http://azimut.dc/rivercrs/debug/Getter@germesSeeder?id=14668
    public function germesSeeder()
    {
        $id = Input::get('id');

        $debug = (Input::get('debug') !== null)?true:false;

        //dd($debug);

        $cruises = $this->cacheWarmUp('germes-cruises', 'array');

        $this->loadCache();

        $cruises = $cruises['тур'];

        ## $id = 'init'; #DEBUG

        if($id == 'init') {
            $ids = [];
            foreach ($cruises as $cruise)
            {
                $ids[] = $cruise['@attributes']['id'];
            }
            $this->json($ids);
            return;
        }

        if(!$debug)
        if($this->germesId($id)) {
            $this->json([
                'message' => "Заезд:$id уже обработан"
            ]);
            return;
        };

        $motorship_name = '';

        #$start = microtime(true);

        # 0.0079

        #$time = microtime(true) - $start;
        #dd(substr($time, 0,6));

        try
        {
            $cruise = $this->getGermesCruise($id, $cruises);
            # 0.0093
            $eds_id = $cruise['@attributes']['id'];
            $ship = $this->getGermesShipName($cruise);
            # 0.0112
            $motorship_name = $ship->name;
            $dates = $this->formatGermesDates($cruise);
            # 0.0120
            $checkin = Checkin::where('eds_code', 'germes')
                ->where('eds_id', $eds_id)
                ->first();

            if($debug) {
                $checkin->delete();
                $checkin = false;
            }

            if (!$checkin) {

                $this->daysDiffCheck($this->diffInIncompliteDays($dates->date, $dates->dateb), $eds_id);
                # 0.0209
                $waybill = $this->getGermesWaybill($cruise);
                # 0.0272
                $checkin = new Checkin;

                $checkin->date = $dates->date;
                $checkin->dateb = $dates->dateb;

                $checkin->desc_1 = '';
                $checkin->motorship_id = $ship->id;
                $checkin->active = 1;
                $checkin->eds_code = 'germes';
                $checkin->eds_id = (int)$eds_id;
                $checkin->waybill_id = $waybill;
                $checkin->save();

                # 0.1013
            } else {
                $checkin->date = $dates->date;
                $checkin->dateb = $dates->dateb;
                $checkin->waybill_id = $this->getGermesWaybill($cruise);
                $checkin->save();
            }
            $this->fillGermesPrices($eds_id, $checkin->id, $ship->id);
            # 4.4045
        }


        catch (Exception $ex) {
            $error = $ex->getMessage();
            JLog::add('error', 'Germes@germesSeeder', $error, 'Ошибка при обработке круиза:'.$eds_id);
            $this->json([
                'message' => 'Ошибка при обработке круиза:'.$eds_id
            ]);
            return;
        }

        #$script_time = microtime(true) - $start;

        # Проверка
        $this->testCheckin($id, 'germes');

        # Удаление кеша (В очередь)
        # app('\Mcmraak\Rivercrs\Controllers\Checkins')->recacheChekin($checkin->id);

        # Поисковый кеш
        //$checkin->updateSearchCache();

        $this->json([
            'message' => 'Обработка: '.$motorship_name.' Заезд #'.$id
        ]);
    }

    public $cabins, $cabins_pivot;

    public function loadCache()
    {
        $this->cabins = $this->cacheWarmUp(
            'germes-cabins',
            'array');

        $this->cabins_pivot = $this->cacheWarmUp(
            'germes-cabins-pivot',
            'array');
    }

    public function getGermesCruise($germes_cruise_id, $cruisesArr)
    {
        foreach ($cruisesArr as $cruise){
            $id = $cruise['@attributes']['id'];
            if($germes_cruise_id == $id) {
                return $cruise;
            }
        }
        return false;
    }

    # Получить имя теплохода из базы Спутник-Гермес
    public function getGermesShipName($cruise)
    {
        $germes_ship_id = $cruise['Теплоход'];
        $germes_ships = $this->cacheWarmUp('germes-ships', 'array');
        $germes_ships = $germes_ships['Теплоход'];
        foreach ($germes_ships as $item)
        {
            if($item['id'] == $germes_ship_id){
                $germes_ship_name = $item['Название'];
                $ship = $this->getMotorship($germes_ship_name,'germes_id', $germes_ship_id);
                return $ship;
            }
        }
        return false;
    }

    # Форматирование дат заезда
    public function formatGermesDates($cruise)
    {
        $d_a = $cruise['ДатаОтплытия'];
        $d_a = $this->mutatorGermesDate($d_a);
        $t_a = $cruise['ВремяОтплытия'];
        $date = $d_a.' '.$t_a.':00';

        $d_b = $cruise['ДатаПрибытия'];
        $d_b = $this->mutatorGermesDate($d_b);
        $t_b = $cruise['ВремяПрибытия'];
        $dateb = $d_b.' '.$t_b.':00';

        return (object) [
            'date' => $date,
            'dateb' => $dateb,
        ];
    }

    # Мутатор даты из 08.05.2018 в 2018-05-08
    public function mutatorGermesDate($date)
    {
        $i = explode('.', $date);
        return $i[2].'-'.$i[1].'-'.$i[0];
    }

    # Получить машрут заезда
    public function getGermesWaybill($cruise)
    {
        $germes_cruise_id = $cruise['@attributes']['id'];
        $germes_trace = $this->cacheWarmUp(
            'germes-trace',
            'array',
            ['id' => $germes_cruise_id]
        );
        preg_match_all('/<span[^>]+>(.+)<\/span>/', $cruise['Маршрут'], $bold_towns);
        $trace = $germes_trace['Tour']['City'];
        $waybill = [];
        $key = 0;
        $max = count($trace) - 1;
        foreach ($trace as $town_name)
        {
            $town_id = $this->getTownId($town_name, 'germes');
            if($bold_towns[1]) {
                $waybill[] = [
                    'town' => $town_id,
                    'excursion' => '',
                    'bold' => (in_array($town_name, $bold_towns[1]) || $key==0 || $key == $max)?1:0
                ];
            } else {
                $waybill[] = [
                    'town' => $town_id,
                    'excursion' => '',
                    'bold' => ($key==0 || $key == $max)?1:0
                ];
            }
            $key++;
        }
        return $waybill;
    }

    public function fillGermesPrices($germes_cruise_id, $checkin_id, $ship_id)
    {
        $ship_id = intval($ship_id);

        $prices = $this->cacheWarmUp(
            'germes-status',
            'array',
            ['id' => $germes_cruise_id]);

        $cabins = $this->cabins;
        $pivot = $this->cabins_pivot;

        //dd($prices['Каюта']); // 83


        //$start = microtime(true);//DEBUG

        $del_q = [];
        $ins_q = [];

        # time 4.3024 - 0.8028
        foreach ($prices['Каюта'] as $germes_price)
        {
            $price_id = $germes_price['id'];

            $cabin = $this->getGermesCabinClass($price_id, $cabins, $pivot);
            if(!$cabin) continue;

            if($this->isCabinNotLet($cabin['Название'], $ship_id)) continue;

            $price_value = (int) $germes_price['ЦенаОснМест'];
            if(!$price_value) continue;
            $cabin_id = $this->getGermesCabin($cabin, $ship_id);
            $queryes = $this->updateCabinPrice($checkin_id, $cabin_id, (int) $price_value, null, 1);
            $del_q[] = $queryes['del'];
            $ins_q[] = $queryes['ins'];
        }

        $del_q = array_unique($del_q);
        $ins_q = array_unique($ins_q);
        $del_q = join(';', $del_q);
        $ins_prefix = 'INSERT INTO `mcmraak_rivercrs_pricing` (`checkin_id`, `cabin_id`, `price_a`, `price_b`, `desc`) VALUES ';
        $ins_q = $ins_prefix . join(',', $ins_q).';';

        \DB::unprepared($del_q);
        \DB::unprepared($ins_q);

        //$time = microtime(true) - $start;
        //dd(substr($time, 0,6));

    }

    public function getGermesCabinClass($price_id, $cabins, $pivot)
    {
        $idClassKauta = false;
        foreach ($pivot['Kauta'] as $pivot_item)
        {
            $id = $pivot_item['@attributes']['id'];
            if($price_id == $id) {
                $idClassKauta = $pivot_item['@attributes']['idClassKauta'];
                continue;
            }
        }

        if(!$idClassKauta) {
            return false;
        }

        foreach ($cabins['Класс'] as $cabinClass)
        {
            $id = $cabinClass['@attributes']['id'];
            if($idClassKauta == $id) {
                return $cabinClass;
            }
        }
        return false;
    }

    # @in: cabin_name, deck_desc
    # @out: cabin_id
    public function getGermesCabin($germes_cabin, $ship_id)
    {

        $germes_cabin_name = $germes_cabin['Название'];
        $germes_cabin_name = trim($germes_cabin_name);
        $germes_cabin_desc = $germes_cabin['Описание'];

        # Есть ли уже эта кабина с кодом источника ?
        $cabin = Cabin::where('germes_name', $germes_cabin_name)
                        ->where('motorship_id', $ship_id)
                        ->first();

        # Если есть возвращаем id
        if($cabin) {
            return $cabin->id;
        }


        $cabin = Cabin::where('germes_name', $germes_cabin_name)
            ->where('motorship_id', $ship_id)
            ->first();

        if(!$cabin)
        $cabin = Cabin::where('category', $germes_cabin_name)
            ->where('motorship_id', $ship_id)
            ->first();

        # Если есть, добавляем код источника и возвращаем id
        if($cabin) {
            $cabin->germes_name = $germes_cabin_name;
            $cabin->save();
            return $cabin->id;
        }

        # Если нет проверям описание
        if(is_array($germes_cabin_desc)){
            $germes_cabin_desc = join('', $germes_cabin_desc);
        }

        # Если нет создаём новую кабину
        $cabin = new Cabin;
        $cabin->motorship_id = $ship_id;
        $cabin->category = $germes_cabin_name;
        $cabin->germes_name = $germes_cabin_name;
        $cabin->desc = $germes_cabin_desc;
        $cabin->save();
        return $cabin->id;
    }

}
