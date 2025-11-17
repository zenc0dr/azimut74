<?php namespace Zen\Worker\Console\waterway;

use Zen\Worker\Classes\ProcessLog;
use Exception;

class WaterwayApiClient
{
    private $timeout;
    private $apiUrl = 'https://api-crs.vodohod.com';
    private $apiToken = 'JYMucmvXoUwDruvgo';
    private $apiLogin = 'azimut-trk+vodohodapi@yandex.ru';
    private $accessToken;
    private $cache;
    private $queryAttempts = 3;

    public function __construct($timeout = 30)
    {
        // Убираем ограничение времени выполнения
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('max_input_time', -1);
        
        $this->timeout = $timeout;
        $this->cache = new WaterwayCache();
    }

    /**
     * Авторизация в API
     */
    private function auth()
    {
        $cacheKey = "waterway_auth_token";
        
        // Проверяем кеш токена
        if ($this->cache->has($cacheKey)) {
            $this->accessToken = $this->cache->get($cacheKey);
            return;
        }
        
        $data = [
            'login' => $this->apiLogin,
            'password' => $this->apiToken
        ];

        $response = $this->httpQuery([
            'method' => 'security.authorise',
            'data' => $data
        ]);

        if ($response->code !== 200 || !isset($response->body['result']['accessToken']['token'])) {
            throw new Exception("Ошибка авторизации в API Waterway");
        }

        $this->accessToken = $response->body['result']['accessToken']['token'];
        
        // Сохраняем токен в кеш (на 1 час)
        $this->cache->put($cacheKey, $this->accessToken);
    }

    /**
     * HTTP запрос к API
     */
    private function httpQuery($opts)
    {
        $default = [
            'method' => null,
            'data' => null,
            'timeout' => null,
        ];

        $opts = (object)array_merge($default, $opts);

        $method = str_replace('.', '/', $opts->method);
        $url = "{$this->apiUrl}/$method";

        ProcessLog::add("Запрос: $url");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        if ($opts->timeout) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $opts->timeout);
        } else {
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        }

        $headers = [];

        if ($opts->data) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            $post = json_encode($opts->data, JSON_UNESCAPED_UNICODE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Content-Length: ' . mb_strlen($post);
        }

        if ($this->accessToken) {
            $headers[] = "Authorization: Bearer {$this->accessToken}";
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ch);
        $code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));

        if ($code !== 200) {
            file_put_contents(storage_path('worker_last_response.txt'), $response);
        }

        curl_close($ch);

        if ($response) {
            $response = json_decode($response, true);
        }

        return (object)[
            'code' => $code,
            'body' => $response
        ];
    }

    /**
     * Запрос к API с кешированием и повторными попытками
     */
    private function wwQuery($method, $data = null, $cacheKey = null)
    {
        if (!$cacheKey) {
            $cacheKey = "waterway_" . md5($method . json_encode($data));
        }
        
        // Проверяем файловый кеш
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        // Авторизация в случае отсутствия ключа
        if (!$this->accessToken) {
            $this->auth();
        }

        // Указание метода
        $opts = ['method' => $method];

        // Инъекция данных, если они есть
        if ($data) {
            $opts['data'] = $data;
        }

        $response = $this->httpQuery($opts);

        // Не прошла аутентификация
        if ($response->code == 403 || $response->code != 200 || intval(@$response->body['code']) != 200) {
            if ($response->code == 403) {
                $this->accessToken = null; // Сбрасываем accessToken
                $this->cache->clear(); // Очищаем кеш токена
            }
            $this->queryAttempts--;
            if ($this->queryAttempts < 0) {
                throw new Exception('error ww1 ' . $method);
            }

            if ($response->code === 500) {
                ProcessLog::add("Критическая ошибка 500, метод=$method");
                throw new Exception('error ww1 ' . $method);
            }

            if ($response->code === 429) {
                ProcessLog::add("Ошибка $response->code: $method (Пауза 5 сек)");
                sleep(5);
            }

            ProcessLog::add("[Error code $response->code] Повтор запроса $method");

            return $this->wwQuery($method, $data, $cacheKey); // Повторяем запрос
        }

        // Сохраняем в файловый кеш (вечный)
        $this->cache->put($cacheKey, $response->body);

        return $response->body;
    }

    /**
     * Получение списка теплоходов
     */
    public function getMotorships()
    {
        $cacheKey = "waterway_motorships";
        
        $response = $this->wwQuery('json.v3.motorships?limit=100', null, $cacheKey);
        
        if (!isset($response['result']['data'])) {
            throw new Exception("API вернул некорректные данные о теплоходах");
        }
        
        $ships = $response['result']['data'];
        
        // Преобразуем в формат, ожидаемый парсером (id => данные)
        $result = [];
        foreach ($ships as $ship) {
            $result[$ship['id']] = [
                'name' => $ship['name'],
                'type' => $ship['type'] ?? null,
                'description' => $ship['description'] ?? ''
            ];
        }
        
        return $result;
    }

    /**
     * Получение списка круизов
     */
    public function getCruises()
    {
        $cacheKey = "waterway_cruises";
        
        // Получаем все круизы с пагинацией
        $nowDay = time(); // Текущая дата в timestamp
        $batch = 100;
        $offset = 0;
        $allCruises = [];
        
        while (true) {
            $queryParams = http_build_query([
                'limit' => $batch,
                'durationFrom' => 2,
                'dateFrom' => $nowDay,
                'offset' => $offset
            ]);
            
            $method = "json.v3.cruises?$queryParams";
            $batchCacheKey = "waterway_cruises_batch_{$offset}";
            
            $response = $this->wwQuery($method, null, $batchCacheKey);
            
            if (!isset($response['result']['data'])) {
                break;
            }
            
            $cruises = $response['result']['data'];
            
            // Преобразуем в формат, ожидаемый парсером (id => данные)
            foreach ($cruises as $cruise) {
                $allCruises[$cruise['id']] = [
                    'name' => $cruise['name'] ?? '',
                    'motorshipId' => $cruise['motorship']['id'] ?? null,
                    'dateStart' => $cruise['dateStart'] ?? null,
                    'dateStop' => $cruise['dateEnd'] ?? null,
                    'days' => $cruise['duration'] ?? 0,
                    'classDescription' => $cruise['classDescription'] ?? null
                ];
            }
            
            $count = intval($response['result']['count'] ?? 0);
            $offset += $batch;
            
            if ($offset >= $count) {
                break;
            }
        }
        
        // Сохраняем полный список в кеш
        $this->cache->put($cacheKey, $allCruises);
        
        return $allCruises;
    }

    /**
     * Получение цен круиза
     */
    public function getCruisePrices($cruiseId)
    {
        $cacheKey = "waterway_prices_{$cruiseId}";
        
        $response = $this->wwQuery("json.v3.cruise.room-tariffs?id=$cruiseId", null, $cacheKey);
        
        if (!isset($response['result'])) {
            // Сохраняем null в кеш, чтобы не запрашивать повторно
            $this->cache->put($cacheKey, null);
            return null;
        }
        
        // Преобразуем в формат, ожидаемый парсером
        $result = [
            'tariffs' => []
        ];
        
        if (isset($response['result']['decks'])) {
            foreach ($response['result']['decks'] as $deck) {
                foreach ($deck['roomClasses'] as $roomClass) {
                    foreach ($roomClass['tariffs'] as $tariff) {
                        $tariffName = $tariff['name'] ?? '';
                        
                        // Обрабатываем только "Тариф Взрослый"
                        if (strpos($tariffName, 'Взрослый') === false && $tariffName !== 'Тариф взрослый') {
                            continue;
                        }
                        
                        if (!isset($result['tariffs'][$tariffName])) {
                            $result['tariffs'][$tariffName] = [
                                'tariff_name' => $tariffName,
                                'prices' => []
                            ];
                        }
                        
                        foreach ($tariff['accommodations'] as $accommodation) {
                            $result['tariffs'][$tariffName]['prices'][] = [
                                'rt_name' => $roomClass['name'] ?? '',
                                'rp_name' => $roomClass['description'] ?? null,
                                'deck_name' => $deck['name'] ?? null,
                                'price_value' => intval(($accommodation['price']['discountedValue'] ?? $accommodation['price']['value'] ?? 0) / 100)
                            ];
                        }
                    }
                }
            }
        }
        
        if (empty($result['tariffs'])) {
            // Сохраняем null в кеш, чтобы не запрашивать повторно
            $this->cache->put($cacheKey, null);
            return null;
        }
        
        return $result;
    }

    /**
     * Получение расписания круиза (cruise-days)
     */
    public function getCruiseRoute($cruiseId)
    {
        $cacheKey = "waterway_route_{$cruiseId}";
        
        // Сначала получаем данные круиза
        $cruiseResponse = $this->wwQuery("json.v3.cruise?id=$cruiseId", null, "waterway_cruise_{$cruiseId}");
        
        if (!isset($cruiseResponse['result']['route'])) {
            // Сохраняем null в кеш, чтобы не запрашивать повторно
            $this->cache->put($cacheKey, null);
            return null;
        }
        
        $routes = $cruiseResponse['result']['route'];
        
        // Преобразуем в формат, ожидаемый парсером
        $result = [];
        $day = 1;
        foreach ($routes as $route) {
            $result[] = [
                'day' => $day++,
                'portName' => $route['name'] ?? '',
                'excursion' => $route['annotation'] ?? '',
                'timeStart' => isset($route['in']) ? date('H:i:s', strtotime($route['in'])) : '00:00:00',
                'timeStop' => isset($route['out']) ? date('H:i:s', strtotime($route['out'])) : '00:00:00'
            ];
        }
        
        if (empty($result)) {
            // Сохраняем null в кеш, чтобы не запрашивать повторно
            $this->cache->put($cacheKey, null);
            return null;
        }
        
        // Сохраняем в файловый кеш (вечный)
        $this->cache->put($cacheKey, $result);
        
        return $result;
    }
}
