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
    private $maxQueryAttempts = 3;
    private $command = null;

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
     * Подключить вывод прогресса в консоль (для долгих прогонов)
     */
    public function setCommand($command)
    {
        $this->command = $command;
        return $this;
    }

    private function consoleLine(string $message)
    {
        if ($this->command && method_exists($this->command, 'line')) {
            $this->command->line('[' . date('H:i:s') . '] ' . $message);
        }
    }

    /**
     * Авторизация в API
     */
    private function auth()
    {
        $cacheKey = "waterway_auth_token";
        
        // Проверяем кеш токена (только если он не null)
        if ($this->cache->has($cacheKey)) {
            $cachedToken = $this->cache->get($cacheKey);
            if ($cachedToken !== null && !empty($cachedToken)) {
                $this->accessToken = $cachedToken;
                ProcessLog::add("Используется кешированный токен авторизации");
                return;
            }
        }
        
        ProcessLog::add("Выполняется авторизация в API Waterway...");
        
        $data = [
            'login' => $this->apiLogin,
            'password' => $this->apiToken
        ];

        $response = $this->httpQuery([
            'method' => 'security.authorise',
            'data' => $data
        ]);

        if ($response->code !== 200 || !isset($response->body['result']['accessToken']['token'])) {
            $errorMsg = "Ошибка авторизации в API Waterway";
            if (isset($response->body['message'])) {
                $errorMsg .= ": " . $response->body['message'];
            }
            if ($response->code !== 200) {
                $errorMsg .= " (HTTP $response->code)";
            }
            throw new Exception($errorMsg);
        }

        $this->accessToken = $response->body['result']['accessToken']['token'];
        
        // Сохраняем токен в кеш (вечный, но будет очищен при 403)
        $this->cache->put($cacheKey, $this->accessToken);
        
        ProcessLog::add("✅ Авторизация успешна, токен получен");
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
        
        // Сбрасываем счётчик попыток для каждого нового запроса (как в старой версии)
        $queryAttempts = $this->maxQueryAttempts;
        
        // Проверяем файловый кеш
        $wasCached = $this->cache->has($cacheKey);
        if ($wasCached) {
            $cached = $this->cache->get($cacheKey);
            // Если в кеше null (запрос был неудачным ранее), возвращаем null
            if ($cached === null) {
                return null;
            }
            return $cached;
        }

        // Авторизация в случае отсутствия ключа
        if (!$this->accessToken) {
            $this->auth();
        }
        
        return $this->wwQueryWithRetries($method, $data, $cacheKey, $queryAttempts, $wasCached);
    }
    
    /**
     * Внутренний метод для выполнения запроса с повторными попытками
     */
    private function wwQueryWithRetries($method, $data, $cacheKey, $queryAttempts, $wasCached)
    {

        // Указание метода
        $opts = ['method' => $method];

        // Инъекция данных, если они есть
        if ($data) {
            $opts['data'] = $data;
        }

        $response = $this->httpQuery($opts);

        // Проверяем наличие ошибки в теле ответа (даже при HTTP 200)
        if (isset($response->body['error'])) {
            $errorCode = $response->body['error']['error_code'] ?? 'unknown';
            $errorMsg = $response->body['error']['error_msg'] ?? 'Unknown error';
            
            // Rate limit в теле ответа
            if ($errorCode == 429) {
                $waitTime = 10;
                ProcessLog::add("⚠️  Rate limit в ответе API (429): $method (Пауза $waitTime сек) - $errorMsg");
                sleep($waitTime);
                return $this->wwQuery($method, $data, $cacheKey); // Повторяем запрос
            }
            
            // Access denied (403) для конкретного ресурса - это нормально, просто нет доступа
            if ($errorCode == 403 && strpos($method, 'security.authorise') === false) {
                ProcessLog::add("⚠️  Access denied (403) для ресурса: $method - $errorMsg");
                // Сохраняем null в кеш, чтобы не запрашивать повторно
                $this->cache->put($cacheKey, null);
                return null; // Возвращаем null вместо исключения
            }
            
            // Другие ошибки в теле ответа
            ProcessLog::add("⚠️  Ошибка в ответе API: код=$errorCode, сообщение=$errorMsg для метода=$method");
            // Сохраняем null в кеш, чтобы не запрашивать повторно
            $this->cache->put($cacheKey, null);
            throw new Exception("API error: $errorCode - $errorMsg");
        }

        // Не прошла аутентификация
        if ($response->code == 403 || $response->code != 200 || intval(@$response->body['code']) != 200) {
            // Обработка Rate Limit (429) - не уменьшаем попытки, просто ждём
            if ($response->code === 429) {
                $waitTime = 10; // Увеличиваем паузу до 10 секунд
                ProcessLog::add("⚠️  Rate limit exceeded (429): $method (Пауза $waitTime сек)");
                sleep($waitTime);
                // Не уменьшаем queryAttempts для rate limit - это не ошибка запроса
                return $this->wwQuery($method, $data, $cacheKey); // Повторяем запрос
            }
            
            // 403 для конкретного ресурса (не авторизация) - нет доступа к ресурсу
            if ($response->code == 403 && strpos($method, 'security.authorise') === false) {
                ProcessLog::add("⚠️  Access denied (403) для ресурса: $method - нет доступа");
                // Сохраняем null в кеш, чтобы не запрашивать повторно
                $this->cache->put($cacheKey, null);
                return null; // Возвращаем null вместо исключения
            }
            
            // 403 на авторизацию - токен истёк
            if ($response->code == 403 && strpos($method, 'security.authorise') !== false) {
                $this->accessToken = null; // Сбрасываем accessToken
                // Очищаем только токен авторизации, не весь кеш
                $this->cache->put("waterway_auth_token", null);
                ProcessLog::add("Ошибка 403: токен истёк, требуется переавторизация");
                // Если была ошибка 403, переавторизуемся перед повторным запросом
                $this->auth();
            }
            
            $queryAttempts--;
            if ($queryAttempts < 0) {
                throw new Exception('error ww1 ' . $method);
            }

            if ($response->code === 500) {
                ProcessLog::add("Критическая ошибка 500, метод=$method");
                throw new Exception('error ww1 ' . $method);
            }

            ProcessLog::add("[Error code $response->code] Повтор запроса $method (осталось попыток: $queryAttempts)");

            return $this->wwQueryWithRetries($method, $data, $cacheKey, $queryAttempts, $wasCached); // Повторяем запрос
        }

        // Сохраняем в файловый кеш (вечный)
        $this->cache->put($cacheKey, $response->body);
        
        // Задержка между запросами к API для избежания rate limit (только для реальных запросов, не из кеша)
        if (!$wasCached) {
            usleep(200000); // 0.2 секунды между запросами к API
        }

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
        // Кеш списка круизов (вечный файловый кеш WaterwayCache)
        $cacheKey = "waterway_cruises";
        
        // Проверяем кеш полного списка
        if ($this->cache->has($cacheKey)) {
            $cachedCruises = $this->cache->get($cacheKey);
            
            // Исправляем days в закешированных данных (конвертируем секунды в дни)
            // Это нужно для данных, закешированных до исправления
            if (is_array($cachedCruises)) {
                foreach ($cachedCruises as $cruiseId => &$cruise) {
                    if (isset($cruise['days']) && $cruise['days'] > 100) {
                        // Если days больше 100, вероятно это секунды, конвертируем в дни
                        $cruise['days'] = (int)($cruise['days'] / 86400);
                    }
                }
                unset($cruise); // Сбрасываем ссылку
            }
            
            $this->consoleLine('Список круизов: кеш hit (' . (is_array($cachedCruises) ? count($cachedCruises) : 0) . ')');
            return $cachedCruises;
        }
        $this->consoleLine('Список круизов: кеш miss, получаем из API батчами...');
        
        // Получаем все круизы с пагинацией
        $nowDay = time(); // Текущая дата в timestamp
        $batch = 100;
        $offset = 0;
        $allCruises = [];
        $maxAttempts = 3; // Максимум попыток при ошибках
        $errorCount = 0;
        
        while (true) {
            // ВАЖНО: параметры dateFrom/durationFrom должны передаваться внутри filter
            // (см. docs/sources/waterway.md). Иначе API может отвечать 403.
            $queryParams = http_build_query([
                'limit' => $batch,
                'offset' => $offset,
                'filter' => [
                    'durationFrom' => [2],
                    'dateFrom' => $nowDay,
                ],
            ]);
            
            $method = "json.v3.cruises?$queryParams";
            $batchCacheKey = "waterway_cruises_batch_{$offset}";
            
            try {
                $response = $this->wwQuery($method, null, $batchCacheKey);
                
                // Если ответ null (ошибка или нет данных), пропускаем этот батч
                if ($response === null) {
                    ProcessLog::add("Ответ null для offset=$offset, пропускаем батч");
                    $offset += $batch;
                    $errorCount++;
                    if ($errorCount >= $maxAttempts) {
                        ProcessLog::add("Превышено количество ошибок подряд, завершаем получение круизов");
                        break;
                    }
                    continue;
                }
                
                if (!isset($response['result']['data'])) {
                    ProcessLog::add("Нет данных в ответе API для offset=$offset, завершаем получение круизов");
                    break;
                }
                
                $cruises = $response['result']['data'];
                
                if (empty($cruises)) {
                    ProcessLog::add("Пустой массив круизов для offset=$offset, завершаем получение круизов");
                    break;
                }
                
                // Преобразуем в формат, ожидаемый парсером (id => данные)
                foreach ($cruises as $cruise) {
                    // Конвертируем duration из секунд в дни
                    $durationSeconds = $cruise['duration'] ?? 0;
                    $days = $durationSeconds > 0 ? (int)($durationSeconds / 86400) : 0;
                    
                    $allCruises[$cruise['id']] = [
                        'name' => $cruise['name'] ?? '',
                        'motorshipId' => $cruise['motorship']['id'] ?? null,
                        'dateStart' => $cruise['dateStart'] ?? null,
                        'dateStop' => $cruise['dateEnd'] ?? null,
                        'days' => $days,
                        'classDescription' => $cruise['classDescription'] ?? null
                    ];
                }
                
                $count = intval($response['result']['count'] ?? 0);
                $offset += $batch;
                $errorCount = 0; // Сбрасываем счётчик ошибок при успехе
                
                ProcessLog::add("Получено круизов: " . count($allCruises) . " (offset=$offset, всего в API: $count)");
                $this->consoleLine("Получено круизов: " . count($allCruises) . " (offset=$offset, всего: $count)");
                
                // Если offset превысил общее количество или получили меньше чем batch - значит это последний батч
                if ($offset >= $count || count($cruises) < $batch) {
                    ProcessLog::add("Достигнут конец списка круизов (offset=$offset, count=$count)");
                    $this->consoleLine("Конец списка круизов (offset=$offset, count=$count)");
                    break;
                }
                
            } catch (Exception $e) {
                $errorCount++;
                ProcessLog::add("Ошибка при получении круизов (offset=$offset, попытка $errorCount/$maxAttempts): " . $e->getMessage());
                $this->consoleLine("Ошибка получения круизов (offset=$offset, попытка $errorCount/$maxAttempts): " . $e->getMessage());
                
                // Если ошибка повторяется несколько раз подряд - прекращаем получение
                if ($errorCount >= $maxAttempts) {
                    ProcessLog::add("Превышено количество попыток при получении круизов. Используем уже полученные " . count($allCruises) . " круизов");
                    $this->consoleLine("Превышено количество попыток. Используем уже полученные: " . count($allCruises));
                    break;
                }
                
                // Пропускаем этот батч и продолжаем со следующего
                $offset += $batch;
                continue;
            }
        }
        
        if (empty($allCruises)) {
            ProcessLog::add("⚠️  Не удалось получить ни одного круиза из API");
            $this->consoleLine("⚠️  Не удалось получить ни одного круиза из API");
            return [];
        }
        
        // Сохраняем полный список в кеш
        $this->cache->put($cacheKey, $allCruises);
        ProcessLog::add("✅ Всего получено круизов для обработки: " . count($allCruises));
        $this->consoleLine("✅ Всего получено круизов для обработки: " . count($allCruises));
        
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
                if (!isset($deck['roomClasses']) || !is_array($deck['roomClasses'])) {
                    continue;
                }
                
                foreach ($deck['roomClasses'] as $roomClass) {
                    if (!isset($roomClass['tariffs']) || !is_array($roomClass['tariffs']) || empty($roomClass['tariffs'])) {
                        continue;
                    }
                    
                    foreach ($roomClass['tariffs'] as $tariff) {
                        // Используем meta_name вместо name (структура API изменилась)
                        $tariffName = $tariff['meta_name'] ?? $tariff['name'] ?? '';
                        
                        // Обрабатываем только "Тариф Взрослый"
                        if (strpos($tariffName, 'Взрослый') === false && $tariffName !== 'Тариф взрослый') {
                            continue;
                        }
                        
                        // Проверяем наличие accommodations
                        if (!isset($tariff['accommodations']) || !is_array($tariff['accommodations']) || empty($tariff['accommodations'])) {
                            continue;
                        }
                        
                        if (!isset($result['tariffs'][$tariffName])) {
                            $result['tariffs'][$tariffName] = [
                                'tariff_name' => $tariffName,
                                'prices' => []
                            ];
                        }
                        
                        foreach ($tariff['accommodations'] as $accommodation) {
                            // Проверяем наличие цены
                            if (!isset($accommodation['price'])) {
                                continue;
                            }
                            
                            $priceValue = intval(($accommodation['price']['discountedValue'] ?? $accommodation['price']['value'] ?? 0) / 100);
                            
                            if ($priceValue > 0) {
                                $result['tariffs'][$tariffName]['prices'][] = [
                                    'rt_name' => $roomClass['name'] ?? '',
                                    'rt_id' => $roomClass['id'] ?? null,
                                    'rt_meta_name' => $roomClass['meta_name'] ?? null,
                                    'rp_name' => $roomClass['description'] ?? null,
                                    'rp_id' => $roomClass['meta_id'] ?? null,
                                    'deck_id' => $deck['id'] ?? null,
                                    'deck_name' => $deck['name'] ?? null,
                                    'deck_meta_id' => $deck['meta_id'] ?? null,
                                    'deck_meta_name' => $deck['meta_name'] ?? null,
                                    'price_value' => $priceValue
                                ];
                            }
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
