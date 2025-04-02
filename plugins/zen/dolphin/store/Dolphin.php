<?php namespace Zen\Dolphin\Store;

/*  Туроператор Дельфин: https://www.delfin-tour.ru
 *  API: https://delfinota.docs.apiary.io
 *  Контактное лицо: Морковиным Константином email:morkovin@delfin.ru tel:(495)280-08-15 доб.191
 *  Логин: AZIMSHL Пароль: 07of0b36
 *  Описание методов: http://8ber.ru/s/jtm
 */

use Carbon\Carbon;
use Zen\Dolphin\Classes\Core;
use Zen\Dolphin\Models\Extour;

class Dolphin extends Core
{
    private $query_attemps = 0, $attemps_pause = 0, $timeout = 10;

    # Запрос непосредственно к Dolphin API
    function query($name, $opts=[])
    {
        # URL API Dolphin
        $url = "https://www.delfin-tour.ru/json/Subagents/$name";

        $cache_time = @$opts['cache_time'] ?? 0;
        $post = @$opts['post'];
        $cache_key = @$opts['cache_key'] ?? $url;

        # CACHE
        $cache = $this->cache('dolphin.parsers');
        $cached_response = $cache->get($cache_key, $cache_time);
        if($cached_response) {
            return $cached_response['response'];
        }

        # Если запрос идёт к прокси
        if(env('APP_ENV') == 'dev') {
            $post = ['url' => $url, 'post' => $post];
            $url = "https://xn----7sbveuzmbgd.xn--p1ai/zen/dolphin/api/proxy:get";
        }

        $response = $this->queryRepeater($url, $post);

        # Запрос выполнен успешно
        $cache->put($cache_key, [
            'url' => $url,
            'post' => $post,
            'response' => $response
        ]);

        return $response;
    }

    private function queryRepeater($url, $post)
    {
        $response = $this->http($url, $post, $this->timeout);

        $code = $response->code;
        $body = $response->body;

        $success = true;

        if($code != 200) {
            $this->log([
                'text' => "Ошибка запроса код:$code",
                'source' => 'Dolphin@query',
                'type' => 'error',
                'dump' => [
                    'url' => $url,
                    'error_code' => $code,
                    'response' => $response
                ]
            ]);

            $success = false;
        }

        if($success && !$body) {
            $this->log([
                'text' => 'Пустой ответ',
                'source' => 'Http@query',
                'type' => 'error',
                'dump' => [
                    'url' => $url,
                    'error' => 'Сервер не вернул данные',
                    'response' => $response
                ]
            ]);

            $success = false;
        }

        if($success) return json_decode($body, true);

        if($this->query_attemps) {
            $this->query_attemps--;
            if($this->attemps_pause) sleep($this->attemps_pause);
            return $this->queryRepeater($url, $post);
        }
    }

    function attemps($query_attemps, $attemps_pause, $timeout = 10)
    {
        $this->query_attemps = $query_attemps;
        $this->attemps_pause = $attemps_pause;
        $this->timeout = $timeout;
        return $this;
    }

    # Получить тур из запроса или кэша если запрос обработан
    # Используется парсером parsers/Tours.php
    function getTour($tour_eid, $backend = false)
    {
        $dolphin = new self;

        if($backend) {
            $dolphin->attemps(5, 5, 15);
        }

        return $dolphin->query("TourContent?id=$tour_eid", [
            'cache_key' => "dolpin.tour.id#$tour_eid"
        ]);
    }

    function getTours($tour_eids)
    {
        $output = [];
        foreach ($tour_eids as $eid) {
            $output[] = $this->getTour($eid);
        }
        return $output;
    }

    ### Поисковый запрос к Dolphin api ###
    # Входные данные (на основании документации: https://delfinota.docs.apiary.io/#reference/1/searchcheckinsnew/post)
    # dates (array) - Массив дат (не более 3х)
    # nights (array) - Массив ночей (не более 3х)
    # adults (int) - Количество взрослых
    # childrens (array) - Возраста детей на день заезда (или путой массив [])
    # case (string) = Areas | Tours - Тип объектов поиска
    # areas (array) - Массив с гео-объектами
    # timeout (integer) - Время на выполнение одного запроса в минутах
    # query_attemps (integer) - Попыток запроса
    # die_on_failure (bool) - Прекратить выполнение скрипта при ошибке
    function searchQuery($opts)
    {

        # Умолчания для опциональных паарметров
        $opts = $this->optsValidate($opts);
        if(isset($opts['error'])) {
            $this->json($opts);
            die;
        };

        $case = (isset($opts['case']))?$opts['case']:'Areas';

        $fileds = null;
        if($case == 'Areas') {
            $fileds = $opts['areas'];
        }

        if($case == 'Tours') {
            $fileds = $opts['tours'];
        }

        $post_data = [
            'CheckInDates' => $opts['dates'],
            'Nights' => $opts['nights'],
            'Adults' => $opts['adults'],
            'ChildAges' => $opts['childrens'],
            'Params' => [
                'Location' => [
                    'Case' => $case,
                    'Fields' => [$fileds]
                ],
                'TourTypes' => [13]
            ],
            'AllowUnquoted' => true,
            'Access' => [
                'Case' => 'LoginPass',
                'Fields' => ['AZIMSHL', '07of0b36']
            ]
        ];

        $cache_key = md5(serialize($opts));

        $response = $this->query('SearchCheckinsNew',[
            'post' => $post_data,
            'cache_key' => $cache_key,
            'timeout' => $opts['timeout'],
            'query_attemps' => $opts['query_attemps'],
            'die_on_failure' => $opts['die_on_failure'],
            'cache_time' => (isset($opts['cache_time']))?$opts['cache_time']:1440
        ]);

        # Валидация
        $error = false;
        if(!$response) $error = 'Пустой response';

        if(!isset($response['SuitableOffers'])) $error = 'SuitableOffers отсутствует';

        if(!$error && !$response['SuitableOffers']) $error = 'Пустой SuitableOffers';

        if($error) {
            $dates = join(', ', $opts['dates']);
            $this->log([
                'text' => "$error Даты:[$dates]",
                'source' => 'DolphinApi@searchQuery',
                'dump' => $post_data
            ]);
            return;
        }

        return $response['SuitableOffers'];
    }

    # Функция валидации вводимых параметров для функции searchQuery()
    private function optsValidate($opts)
    {
        if(!isset($opts['timeout'])) $opts['timeout'] = 5;
        if(!isset($opts['query_attemps'])) $opts['query_attemps']  = 0;
        if(!isset($opts['die_on_failure'])) $opts['die_on_failure']  = true;

        if(!is_array($opts['dates'])) return [
            'error' => 'Даты не являются массивом'
        ];

        if(count($opts['dates'])>3) return [
            'error' => "Количество дат должно не больше 3, сейчас ".count($opts['dates'])
        ];

        if(!@$opts['nights']) return [
            'error' => 'Отсутствует количество ночей'
        ];

        if(!is_array($opts['nights'])) return [
            'error' => 'Количество ночей должно храниться в массиве'
        ];

        if(!@$opts['adults']) return [
            'error' => 'Отсутствует количество взрослых'
        ];

        if(!@$opts['childrens']) $opts['childrens'] = [];


        if(!@$opts['areas'] && !@$opts['tours']) return [
            'error' => 'Отсутствуют географические объекты'
        ];

        return $opts;
    }

    function queryHandler($query)
    {
        $dates = $this->generateDateRange(
            Carbon::parse($query['date_of']),
            Carbon::parse($query['date_to'])
        );

        # Тут заменяем гео объекты вида 2:336 на идентификаторы дельфина (Комплексные туры отсеиваются)
        $areas = $this->geoObjectsToAreas($query['geo_objects']);

        # Получем идентификаторы комплексных туров для комплексных туров
        $extours_eids = $this->getExtoursEids($query['geo_objects']);

        # Тут фильтруются НЕ дельфиновские гео-объекты (у которых в начале loc_)
        $areas = collect($areas)->filter(function ($item){
            return preg_match('/^\d+$/', $item);
        })->toArray();

        # Заменяем geo_objects на areas
        unset($query['geo_objects']);
        $query['areas'] = $areas;


        $dates_packs = [];
        $pack_index = 0;
        foreach ($dates as $date) {
            if(@count($dates_packs[$pack_index]) < 3) {
                $dates_packs[$pack_index][] = $date;
            } else {
                $pack_index++;
                $dates_packs[$pack_index][] = $date;
            }
        }

        $nights_packs_count = intval(ceil(count($query['nights'])/3));

        $nights_packs = [];
        $night_index = 0;
        for($i=0;$i<$nights_packs_count;$i++) {
            for($ii=0;$ii<3;$ii++) {
                if(isset($query['nights'][$night_index])) {
                    $nights_packs[$i][] = $query['nights'][$night_index];
                    $night_index++;
                } else break;
            }
        }

        $query_packs = [];

        foreach ($dates_packs as $dates_pack) {
            foreach ($nights_packs as $nights_pack) {
                $query_packs[] = [
                    'dates' => $dates_pack,
                    'nights' => $nights_pack
                ];
            }
        }

        $queries = [];
        foreach ($query_packs as $query_pack) {

            if($query['areas']) {
                $queries[] = [
                    'dates' => $query_pack['dates'],
                    'nights' => $query_pack['nights'],
                    'areas' => $query['areas'],
                    'adults' => $query['adults'],
                    'childrens' => @$query['children_ages'],
                    'die_on_failure' => false
                ];
            }

            # Добавляем комплексные туры
            if($extours_eids) {
                $queries[] = [
                    'case' => 'Tours',
                    'dates' => $query_pack['dates'],
                    'nights' => $query_pack['nights'],
                    'tours' => $extours_eids,
                    'adults' => $query['adults'],
                    'childrens' => @$query['children_ages'],
                    'die_on_failure' => false
                ];
            }
        }

        return $queries;
    }

    # Получить дельфиновские идентификаторы туров, для комплексных туров
    private function getExtoursEids($geo_objects)
    {
        $extours_ids = $this->getExtoursIds($geo_objects);
        $extours = Extour::whereIn('id', $extours_ids)->get();

        $eids = [];
        foreach ($extours as $extour) {
            $eids = array_merge($eids, $extour->extours_eids_arr);
        }

        return $eids;
    }

    # Найти в гео-объектах комплексные туры и получить их id
    private function getExtoursIds($geo_objects)
    {
        $extours_eids = [];
        foreach ($geo_objects as $geo_object) {
            if(preg_match('/:ex(\d+)$/', $geo_object, $m)) {
                $extours_eids[] = intval($m[1]);
            }
        }
        return $extours_eids;
    }

    # Генератор заданий для потока
    function jobsGenerator($query)
    {
        $jobs = [];
        $dolphin_queries = $this->queryHandler($query);
        foreach ($dolphin_queries as $dolphin_query) {
            $jobs[] = [
                'handler' => 'Dolphin:searchQuery',
                'data' => $dolphin_query
            ];
        }

        return $jobs;
    }

    # Генератор дат из диапазона
    private function generateDateRange(Carbon $start_date, Carbon $end_date)
    {
        $dates = [];
        for($date = $start_date; $date->lte($end_date); $date->addDay())
        {
            $dates[] = $date->format('d.m.Y');
        }
        return $dates;
    }

}
