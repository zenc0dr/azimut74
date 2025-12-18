<?php namespace Zen\Worker\Console\gama;

use Zen\Worker\Classes\Http;
use Zen\Worker\Classes\ProcessLog;
use Exception;

class GamaApiClient
{
    private $timeout;
    private $key = 'gIOZhOWvGDa177aLNh0rofIO';
    private $baseUrl = 'https://gama-nn.ru/satellite/xml/';
    private $routeUrl = 'https://gama-nn.ru/satellite/route/';
    private $cache;

    public function __construct($timeout = 30)
    {
        // Убираем ограничение времени выполнения
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('max_input_time', -1);
        
        $this->timeout = $timeout;
        $this->cache = new GamaCache();
    }

    /**
     * Скачивание архивов Gama
     */
    public function downloadGamaArchives()
    {
        $zip_url = $this->baseUrl . 'zip/?key=' . $this->key;
        $storage_path = base_path('storage/gama_arc');
        $zip_file = $storage_path . '/gama.zip';
        
        // Создаем директорию если не существует
        if (!is_dir($storage_path)) {
            mkdir($storage_path, 0755, true);
        }
        
        // Очищаем старые файлы
        shell_exec("rm -rf " . escapeshellarg($storage_path) . "/*");
        
        // Скачиваем архив (используем curl вместо wget)
        $curlCmd = "curl -sL -o " . escapeshellarg($zip_file) . " " . escapeshellarg($zip_url);
        shell_exec($curlCmd);
        
        // Проверяем что файл скачался
        if (!file_exists($zip_file) || filesize($zip_file) < 1000) {
            throw new Exception("Не удалось скачать архив Gama. Проверьте API ключ.");
        }
        
        // Распаковываем
        shell_exec("unzip -o " . escapeshellarg($zip_file) . " -d " . escapeshellarg($storage_path));
        
        // Проверяем что файлы распаковались
        if (!file_exists($storage_path . '/navigation.xml')) {
            throw new Exception("Архив не содержит navigation.xml или не удалось распаковать.");
        }
    }

    /**
     * Чтение данных из XML файла
     */
    public function getGamaFileData($fileName)
    {
        $filePath = base_path("storage/gama_arc/$fileName");
        if (!file_exists($filePath)) {
            throw new Exception("Файл $fileName не найден");
        }
        
        $xmlContent = file_get_contents($filePath);
        return $this->xmlToArray($xmlContent);
    }

    /**
     * Получение данных маршрута через API в реальном времени
     */
    public function getGamaRouteData($routeId)
    {
        // Создаем ключ кеша для этого маршрута
        $cacheKey = "gama_route_{$routeId}";
        
        // Проверяем кеш в JSON файлах
        if ($this->cache->has($cacheKey)) {
            $cachedData = $this->cache->get($cacheKey);
            // Если круиз был отозван — возвращаем null
            if (is_array($cachedData) && isset($cachedData['_expired'])) {
                return null;
            }
            return $cachedData;
        }
        
        // Сбрасываем время выполнения перед каждым HTTP запросом
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        
        $url = $this->routeUrl . $routeId . '/?key=' . $this->key;
        
        // Делаем запрос без автоматического парсинга XML
        $rawResponse = $this->fetchRawResponse($url);
        
        // Проверяем на ошибки отозванного круиза
        if ($rawResponse === false || strpos($rawResponse, 'Sub expired') !== false) {
            // Сохраняем осмысленную информацию для отладки
            $expiredData = [
                '_expired' => true,
                '_reason' => 'Sub expired',
                '_route_id' => $routeId,
                '_cached_at' => date('Y-m-d H:i:s'),
                '_message' => 'Круиз отозван или завершён'
            ];
            $this->cache->put($cacheKey, $expiredData);
            return null;
        }
        
        // Парсим XML в массив
        $responseArray = $this->xmlToArray($rawResponse);
        
        // Если парсинг не удался — сохраняем информацию об ошибке
        if ($responseArray === false || $responseArray === null) {
            $errorData = [
                '_error' => true,
                '_reason' => 'Invalid XML',
                '_route_id' => $routeId,
                '_cached_at' => date('Y-m-d H:i:s'),
                '_raw_preview' => substr($rawResponse, 0, 200)
            ];
            $this->cache->put($cacheKey, $errorData);
            ProcessLog::add("Круиз $routeId: невалидный XML ответ");
            return null;
        }

        // Кэшируем успешный ответ
        try {
            $this->cache->put($cacheKey, $responseArray);
        } catch (Exception $e) {
            ProcessLog::add("Ошибка сохранения кеша для маршрута $routeId: " . $e->getMessage());
        }
        
        return $responseArray;
    }
    
    /**
     * Получение сырого ответа без парсинга
     */
    private function fetchRawResponse($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error || $httpCode !== 200) {
            ProcessLog::add("HTTP ошибка для $url: $error (код $httpCode)");
            return false;
        }
        
        return $response;
    }

    /**
     * Получение списка ID круизов из навигации
     */
    public function getCruiseIds()
    {
        $navigationData = $this->getGamaFileData('navigation.xml');
        
        $ids = [];
        if (isset($navigationData['NavigationList']['Navigation'])) {
            $navigations = $navigationData['NavigationList']['Navigation'];
            if (isset($navigations['@attributes'])) {
                $navigations = [$navigations];
            }
            
            foreach ($navigations as $navigation) {
                if (isset($navigation['RouteList']['Route'])) {
                    $routes = $navigation['RouteList']['Route'];
                    if (isset($routes['@attributes'])) {
                        $routes = [$routes];
                    }
                    
                    foreach ($routes as $route) {
                        $cruiseId = $route['@attributes']['id'] ?? null;
                        if ($cruiseId) {
                            $ids[] = $cruiseId;
                        }
                    }
                }
            }
        }
        
        return $ids;
    }

    /**
     * Получение данных навигации
     */
    public function getNavigationData()
    {
        return $this->getGamaFileData('navigation.xml');
    }

    /**
     * Получение справочных данных
     */
    public function getGenericData()
    {
        return $this->getGamaFileData('dir_generic.xml');
    }

    /**
     * Получение данных о ценах для навигации
     */
    public function getNavigationAvailableData($navigationId)
    {
        try {
            return $this->getGamaFileData("navigation_{$navigationId}_available.xml");
        } catch (Exception $e) {
            return null; // Файл может отсутствовать
        }
    }

    /**
     * Конвертация XML в массив
     */
    private function xmlToArray($xmlString)
    {
        $xml = simplexml_load_string($xmlString);
        return json_decode(json_encode($xml), true);
    }

    /**
     * Получение API ключа
     */
    public function getApiKey()
    {
        return $this->key;
    }
}