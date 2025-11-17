<?php namespace Zen\Worker\Console\germes;

use Zen\Worker\Classes\Http;
use Zen\Worker\Classes\ProcessLog;
use Exception;

class GermesApiClient
{
    private $timeout;
    private $baseUrl = 'https://river.sputnik-germes.ru/XML/';
    private $cache;

    public function __construct($timeout = 30)
    {
        // Убираем ограничение времени выполнения
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('max_input_time', -1);
        
        $this->timeout = $timeout;
        $this->cache = new GermesCache();
    }

    /**
     * Получение списка теплоходов
     */
    public function getShips()
    {
        $cacheKey = 'germes_ships';
        
        // Проверяем кеш
        $cachedData = $this->cache->get($cacheKey);
        if ($cachedData !== null) {
            return $cachedData;
        }
        
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        
        $url = $this->baseUrl . 'ListTeplohod.php';
        
        $http = new Http();
        $http_query = $http->setTimout($this->timeout)
            ->query($url, 'xml');

        if ($http_query->error) {
            throw new Exception("Ошибка получения списка теплоходов: " . $http_query->error);
        }

        // Нормализуем ответ к массиву
        $responseContent = $http_query->response;
        if (is_string($responseContent)) {
            $response = $this->xmlToArray($responseContent);
        } else {
            $response = $responseContent;
        }
        
        // Сохраняем в кеш
        $this->cache->put($cacheKey, $response);
        
        return $response;
    }

    /**
     * Получение классов кают
     */
    public function getCabinCategories()
    {
        $cacheKey = 'germes_cabin_categories';
        
        // Проверяем кеш
        $cachedData = $this->cache->get($cacheKey);
        if ($cachedData !== null) {
            return $cachedData;
        }
        
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        
        $url = $this->baseUrl . 'ListClassKauta.php';
        
        $http = new Http();
        $http_query = $http->setTimout($this->timeout)
            ->query($url, 'xml');

        if ($http_query->error) {
            throw new Exception("Ошибка получения классов кают: " . $http_query->error);
        }

        // Нормализуем ответ к массиву
        $responseContent = $http_query->response;
        if (is_string($responseContent)) {
            $response = $this->xmlToArray($responseContent);
        } else {
            $response = $responseContent;
        }
        
        // Сохраняем в кеш
        $this->cache->put($cacheKey, $response);
        
        return $response;
    }

    /**
     * Получение pivot таблицы кают
     */
    public function getCabinsPivot()
    {
        $cacheKey = 'germes_cabins_pivot';
        
        // Проверяем кеш
        $cachedData = $this->cache->get($cacheKey);
        if ($cachedData !== null) {
            return $cachedData;
        }
        
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        
        $url = $this->baseUrl . 'ListKauta.php';
        
        $http = new Http();
        $http_query = $http->setTimout($this->timeout)
            ->query($url, 'xml');

        if ($http_query->error) {
            throw new Exception("Ошибка получения pivot таблицы кают: " . $http_query->error);
        }

        // Нормализуем ответ к массиву
        $responseContent = $http_query->response;
        if (is_string($responseContent)) {
            $response = $this->xmlToArray($responseContent);
        } else {
            $response = $responseContent;
        }
        
        // Сохраняем в кеш
        $this->cache->put($cacheKey, $response);
        
        return $response;
    }

    /**
     * Получение списка круизов
     */
    public function getCruises()
    {
        $cacheKey = 'germes_cruises';
        
        // Проверяем кеш
        $cachedData = $this->cache->get($cacheKey);
        if ($cachedData !== null) {
            return $cachedData;
        }
        
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        
        $url = $this->baseUrl . 'exportTur.php';
        
        $http = new Http();
        $http_query = $http->setTimout($this->timeout)
            ->query($url, 'xml');

        if ($http_query->error) {
            throw new Exception("Ошибка получения списка круизов: " . $http_query->error);
        }

        // Нормализуем ответ к массиву
        $responseContent = $http_query->response;
        if (is_string($responseContent)) {
            $response = $this->xmlToArray($responseContent);
        } else {
            $response = $responseContent;
        }
        
        // Сохраняем в кеш
        $this->cache->put($cacheKey, $response);
        
        return $response;
    }

    /**
     * Получение маршрута круиза
     */
    public function getCruiseTrace($cruiseId)
    {
        $cacheKey = "germes_trace_{$cruiseId}";
        
        // Проверяем кеш
        $cachedData = $this->cache->get($cacheKey);
        if ($cachedData !== null) {
            return $cachedData;
        }
        
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        
        $url = $this->baseUrl . 'exportTrace.php';
        $params = ['tur' => $cruiseId];
        
        $http = new Http();
        $http_query = $http->setTimout($this->timeout)
            ->dataGet($params)
            ->query($url, 'xml');

        if ($http_query->error) {
            ProcessLog::add("Ошибка получения маршрута для круиза $cruiseId: " . $http_query->error);
            // Сохраняем null в кеш, чтобы не запрашивать повторно
            $this->cache->put($cacheKey, null);
            return null;
        }

        // Нормализуем ответ к массиву
        $responseContent = $http_query->response;
        if (is_string($responseContent)) {
            $response = $this->xmlToArray($responseContent);
        } else {
            $response = $responseContent;
        }
        
        // Сохраняем в кеш
        $this->cache->put($cacheKey, $response);
        
        return $response;
    }

    /**
     * Получение цен кают для круиза
     */
    public function getCruisePrices($cruiseId)
    {
        $cacheKey = "germes_prices_{$cruiseId}";
        
        // Проверяем кеш
        $cachedData = $this->cache->get($cacheKey);
        if ($cachedData !== null) {
            return $cachedData;
        }
        
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        
        $url = $this->baseUrl . 'exportKauta.php';
        $params = ['tur' => $cruiseId];
        
        $http = new Http();
        $http_query = $http->setTimout($this->timeout)
            ->dataGet($params)
            ->query($url, 'xml');

        if ($http_query->error) {
            ProcessLog::add("Ошибка получения цен для круиза $cruiseId: " . $http_query->error);
            // Сохраняем null в кеш, чтобы не запрашивать повторно
            $this->cache->put($cacheKey, null);
            return null;
        }

        // Нормализуем ответ к массиву
        $responseContent = $http_query->response;
        if (is_string($responseContent)) {
            $response = $this->xmlToArray($responseContent);
        } else {
            $response = $responseContent;
        }
        
        // Сохраняем в кеш
        $this->cache->put($cacheKey, $response);
        
        return $response;
    }

    /**
     * Конвертация XML в массив
     */
    private function xmlToArray($xmlString)
    {
        if (empty($xmlString)) {
            return null;
        }
        
        try {
            $xml = simplexml_load_string($xmlString);
            if ($xml === false) {
                throw new Exception("Ошибка парсинга XML");
            }
            return json_decode(json_encode($xml), true);
        } catch (Exception $e) {
            throw new Exception("Ошибка конвертации XML в массив: " . $e->getMessage());
        }
    }

    /**
     * Получение объекта кеша
     */
    public function getCache()
    {
        return $this->cache;
    }
}

