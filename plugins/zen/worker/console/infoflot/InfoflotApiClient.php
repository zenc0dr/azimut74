<?php namespace Zen\Worker\Console\infoflot;

use Zen\Worker\Classes\Http;
use Zen\Worker\Classes\ProcessLog;
use Cache;
use Exception;

class InfoflotApiClient
{
    private $timeout;
    private $apiKey;
    private $baseUrl = 'https://restapi.infoflot.com';

    public function __construct($apiKey, $timeout = 30)
    {
        // Убираем ограничение времени выполнения
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('max_input_time', -1);
        
        $this->timeout = $timeout;
        $this->apiKey = $apiKey;
    }

    /**
     * Получение списка судов
     */
    public function getShips($page = 1, $limit = 100)
    {
        $cacheKey = "infoflot_ships_page_{$page}_limit_{$limit}";
        
        // Проверяем кеш (6 часов)
        $cachedData = Cache::get($cacheKey);
        if ($cachedData) {
            return $cachedData;
        }
        
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        
        $url = $this->baseUrl . '/ships?' . http_build_query([
            'key' => $this->apiKey,
            'page' => $page,
            'limit' => $limit
        ]);
        
        $http = new Http();
        $http_query = $http->setTimout($this->timeout)
            ->query($url, 'json');

        if ($http_query->error) {
            throw new Exception("Ошибка получения списка судов (страница $page): " . $http_query->error);
        }

        $response = $http_query->response;
        
        // Кешируем на 6 часов
        Cache::put($cacheKey, $response, 21600);
        
        return $response;
    }

    /**
     * Получение круизов для судна
     */
    public function getCruisesByShip($shipId, $page = 1, $limit = 500, $date = null)
    {
        if (!$date) {
            $date = date('Y-m-d');
        }
        
        $cacheKey = "infoflot_cruises_ship_{$shipId}_page_{$page}_date_{$date}";
        
        // Проверяем кеш (6 часов)
        $cachedData = Cache::get($cacheKey);
        if ($cachedData) {
            return $cachedData;
        }
        
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        
        $url = $this->baseUrl . '/cruises?' . http_build_query([
            'key' => $this->apiKey,
            'ship' => $shipId,
            'page' => $page,
            'date' => $date,
            'limit' => $limit
        ]);
        
        $http = new Http();
        $http_query = $http->setTimout($this->timeout)
            ->query($url, 'json');

        if ($http_query->error) {
            // Если "Not found" или "Resource not found" - это нормально, значит нет круизов
            if (strpos($http_query->error, 'Not found') !== false || 
                strpos($http_query->error, 'Resource not found') !== false) {
                return null;
            }
            throw new Exception("Ошибка получения круизов для судна $shipId (страница $page): " . $http_query->error);
        }

        $response = $http_query->response;
        
        // Проверяем структуру ответа
        if (!is_array($response)) {
            return null;
        }
        
        // Если ответ содержит ошибку
        if (isset($response['status']) && $response['status'] == 404) {
            return null;
        }
        
        // Кешируем на 6 часов
        Cache::put($cacheKey, $response, 21600);
        
        return $response;
    }

    /**
     * Получение цен кают для круиза
     */
    public function getCruiseCabins($cruiseId)
    {
        $cacheKey = "infoflot_cruise_{$cruiseId}_cabins";
        
        // Проверяем кеш (6 часов)
        $cachedData = Cache::get($cacheKey);
        if ($cachedData) {
            return $cachedData;
        }
        
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        
        $url = $this->baseUrl . "/cruises/{$cruiseId}/cabins?" . http_build_query([
            'key' => $this->apiKey
        ]);
        
        $http = new Http();
        $http_query = $http->setTimout($this->timeout)
            ->query($url, 'json');

        if ($http_query->error) {
            // Если "Not found" или "Resource not found" - значит нет данных о ценах
            if (strpos($http_query->error, 'Not found') !== false || 
                strpos($http_query->error, 'Resource not found') !== false) {
                return null;
            }
            throw new Exception("Ошибка получения цен для круиза $cruiseId: " . $http_query->error);
        }

        $response = $http_query->response;
        
        // Кешируем на 6 часов
        Cache::put($cacheKey, $response, 21600);
        
        return $response;
    }
}

