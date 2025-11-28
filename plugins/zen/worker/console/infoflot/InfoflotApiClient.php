<?php namespace Zen\Worker\Console\infoflot;

use Zen\Worker\Classes\Http;
use Zen\Worker\Classes\ProcessLog;
use Exception;

class InfoflotApiClient
{
    private $timeout;
    private $apiKey;
    private $baseUrl = 'https://restapi.infoflot.com';
    private $cache;

    public function __construct($apiKey, $timeout = 30)
    {
        // Убираем ограничение времени выполнения
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('max_input_time', -1);
        
        $this->timeout = $timeout;
        $this->apiKey = $apiKey;
        $this->cache = new InfoflotCache();
    }

    /**
     * Получение списка судов
     */
    public function getShips($page = 1, $limit = 100)
    {
        $cacheKey = "infoflot_ships_page_{$page}_limit_{$limit}";
        
        // Проверяем файловый кеш
        $cachedData = $this->cache->get($cacheKey);
        if ($cachedData !== null) {
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

        // Обход проблемы с DNS в контейнере: если ошибка DNS или пустой ответ, пробуем с CURLOPT_RESOLVE
        if ($http_query->error && (strpos($http_query->error, 'Could not resolve host') !== false || 
            strpos($http_query->error, 'Пустой ответ') !== false || 
            !$http_query->response)) {
            ProcessLog::add("Проблема с DNS, используем обход через IP адрес");
            // Используем прямой запрос через curl с CURLOPT_RESOLVE
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_RESOLVE, ['restapi.infoflot.com:443:178.248.239.118']);
            $response = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($code == 200 && $response) {
                $response = json_decode($response, true);
                if ($response) {
                    // Сохраняем в файловый кеш (вечный)
                    $this->cache->put($cacheKey, $response);
                    return $response;
                }
            }
            
            throw new Exception("Ошибка получения списка судов (страница $page) через IP: " . $error);
        }

        if ($http_query->error) {
            throw new Exception("Ошибка получения списка судов (страница $page): " . $http_query->error);
        }

        $response = $http_query->response;
        
        // Сохраняем в файловый кеш (вечный)
        $this->cache->put($cacheKey, $response);
        
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
        
        // Проверяем файловый кеш
        $cachedData = $this->cache->get($cacheKey);
        if ($cachedData !== null) {
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

        // Обход проблемы с DNS в контейнере
        if ($http_query->error && (strpos($http_query->error, 'Could not resolve host') !== false || 
            strpos($http_query->error, 'Пустой ответ') !== false || 
            !$http_query->response)) {
            ProcessLog::add("Проблема с DNS для круизов судна $shipId, используем обход через IP адрес");
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_RESOLVE, ['restapi.infoflot.com:443:178.248.239.118']);
            $response = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($code == 200 && $response) {
                $response = json_decode($response, true);
                if ($response) {
                    $this->cache->put($cacheKey, $response);
                    return $response;
                }
            }
            
            if (strpos($error, 'Not found') !== false || $code == 404) {
                return null;
            }
            
            throw new Exception("Ошибка получения круизов для судна $shipId (страница $page) через IP: " . $error);
        }

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
        
        // Сохраняем в файловый кеш (вечный)
        $this->cache->put($cacheKey, $response);
        
        return $response;
    }

    /**
     * Получение цен кают для круиза
     */
    public function getCruiseCabins($cruiseId)
    {
        $cacheKey = "infoflot_cruise_{$cruiseId}_cabins";
        
        // Проверяем файловый кеш
        $cachedData = $this->cache->get($cacheKey);
        if ($cachedData !== null) {
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

        // Обход проблемы с DNS в контейнере
        if ($http_query->error && (strpos($http_query->error, 'Could not resolve host') !== false || 
            strpos($http_query->error, 'Пустой ответ') !== false || 
            !$http_query->response)) {
            ProcessLog::add("Проблема с DNS для цен круиза $cruiseId, используем обход через IP адрес");
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_RESOLVE, ['restapi.infoflot.com:443:178.248.239.118']);
            $response = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($code == 200 && $response) {
                $response = json_decode($response, true);
                if ($response) {
                    $this->cache->put($cacheKey, $response);
                    return $response;
                }
            }
            
            if (strpos($error, 'Not found') !== false || $code == 404) {
                return null;
            }
            
            throw new Exception("Ошибка получения цен для круиза $cruiseId через IP: " . $error);
        }

        if ($http_query->error) {
            // Если "Not found" или "Resource not found" - значит нет данных о ценах
            if (strpos($http_query->error, 'Not found') !== false || 
                strpos($http_query->error, 'Resource not found') !== false) {
                return null;
            }
            throw new Exception("Ошибка получения цен для круиза $cruiseId: " . $http_query->error);
        }

        $response = $http_query->response;
        
        // Сохраняем в файловый кеш (вечный)
        $this->cache->put($cacheKey, $response);
        
        return $response;
    }
}

