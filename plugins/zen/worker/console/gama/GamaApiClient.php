<?php namespace Zen\Worker\Console\gama;

use Zen\Worker\Classes\Http;
use Zen\Worker\Classes\ProcessLog;
use Cache;
use Exception;

class GamaApiClient
{
    private $timeout;
    private $key = 'gIOZhOWvGDa177aLNh0rofIO';
    private $baseUrl = 'https://gama-nn.ru/satellite/xml/';
    private $routeUrl = 'https://gama-nn.ru/satellite/route/';

    public function __construct($timeout = 30)
    {
        // Убираем ограничение времени выполнения
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('max_input_time', -1);
        
        $this->timeout = $timeout;
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
        
        // Скачиваем архив
        shell_exec("wget -O " . escapeshellarg($zip_file) . " " . escapeshellarg($zip_url));
        
        // Распаковываем
        shell_exec("unzip -o " . escapeshellarg($zip_file) . " -d " . escapeshellarg($storage_path));
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
        
        // Проверяем кеш (6 часов)
        $cachedData = Cache::get($cacheKey);
        if ($cachedData) {
            return $cachedData;
        }
        
        // Сбрасываем время выполнения перед каждым HTTP запросом
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        
        $url = $this->routeUrl . $routeId . '/?key=' . $this->key;
        
        $http = new Http();
        $http_query = $http->setTimout($this->timeout)
            ->query($url, 'xml');

        if ($http_query->error) {
            throw new Exception("Ошибка получения данных маршрута #$routeId: " . $http_query->error);
        }

        // Содержимое ответа
        $responseContent = $http_query->response;

        // Безопасное логирование
        if (is_string($responseContent)) {
            ProcessLog::add("Ответ API для круиза $routeId: " . substr($responseContent, 0, 100) . "...");
        } else {
            ProcessLog::add("Ответ API для круиза $routeId: тип=" . gettype($responseContent));
        }

        // Если строка и содержит "Sub expired" — данных нет
        if (is_string($responseContent) && strpos($responseContent, 'Sub expired') !== false) {
            ProcessLog::add("Круиз $routeId: нет данных в данный момент (Sub expired)");
            return null;
        }

        // Нормализуем ответ к массиву
        if (is_string($responseContent)) {
            $responseArray = $this->xmlToArray($responseContent);
        } else {
            $responseArray = $responseContent;
        }

        // Кэшируем массив
        if ($responseArray) {
            Cache::put($cacheKey, $responseArray, 360);
        }
        
        return $responseArray;
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