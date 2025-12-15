<?php namespace Zen\Worker\Console\volga;

use Zen\Worker\Classes\Convertor;
use Zen\Worker\Classes\ProcessLog;
use Exception;

class VolgaApiClient
{
    private $timeout;
    private $nextUrl;
    private $xmlFilePath;
    private $legacyXmlFilePath;

    public function __construct($nextUrl, $timeout = 30)
    {
        // Убираем ограничение времени выполнения
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('max_input_time', -1);
        
        $this->timeout = $timeout;
        $this->nextUrl = $nextUrl;
        $this->legacyXmlFilePath = storage_path('volga_next_url.xml');

        // Единый кеш парсеров: storage/parsers_cache/volga/volga_next_url.xml
        $preferredDir = storage_path('parsers_cache/volga');
        if (!is_dir($preferredDir)) {
            @mkdir($preferredDir, 0775, true);
        }
        $preferredPath = rtrim($preferredDir, '/') . '/volga_next_url.xml';

        // Миграция: если legacy-файл есть, а нового нет — копируем в новый кеш.
        if (!file_exists($preferredPath) && file_exists($this->legacyXmlFilePath)) {
            @copy($this->legacyXmlFilePath, $preferredPath);
        }

        $this->xmlFilePath = $preferredPath;
    }

    /**
     * Скачивание XML файла через PHP
     */
    public function downloadXmlFile(bool $force = false)
    {
        if (empty($this->nextUrl)) {
            throw new Exception('URL источника данных не указан (next_url)');
        }

        // Кеш: если файл уже есть и не просили принудительно — используем его.
        if (!$force && file_exists($this->xmlFilePath) && filesize($this->xmlFilePath) > 0) {
            ProcessLog::add("XML кеш hit: {$this->xmlFilePath} (" . number_format(filesize($this->xmlFilePath)) . " байт)");
            return $this->xmlFilePath;
        }

        ProcessLog::add("Скачивание XML файла из: {$this->nextUrl}");
        
        // Скачиваем файл через PHP file_get_contents или curl
        $context = stream_context_create([
            'http' => [
                'timeout' => $this->timeout,
                'method' => 'GET',
                'header' => [
                    'User-Agent: Mozilla/5.0 (compatible; VolgaParser/1.0)'
                ]
            ]
        ]);
        
        $xmlContent = @file_get_contents($this->nextUrl, false, $context);
        
        if ($xmlContent === false) {
            // Пробуем через curl если file_get_contents не работает
            if (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $this->nextUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; VolgaParser/1.0)');
                $xmlContent = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);
                
                if ($xmlContent === false || $httpCode !== 200) {
                    throw new Exception("Ошибка при скачивании XML файла через curl. HTTP код: $httpCode. Ошибка: $curlError");
                }
            } else {
                throw new Exception("Ошибка при скачивании XML файла. file_get_contents и curl недоступны");
            }
        }
        
        if (empty($xmlContent)) {
            throw new Exception('XML файл пуст или не был скачан');
        }
        
        // Гарантируем директорию кеша
        $dir = dirname($this->xmlFilePath);
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0775, true)) {
                throw new Exception("Не удалось создать директорию кеша Volga: {$dir}");
            }
        }

        // Сохраняем файл
        $result = file_put_contents($this->xmlFilePath, $xmlContent);
        
        if ($result === false) {
            throw new Exception("Ошибка при сохранении XML файла: {$this->xmlFilePath}");
        }
        
        ProcessLog::add("XML файл успешно скачан: {$this->xmlFilePath} (" . number_format(strlen($xmlContent)) . " байт)");
        
        return $this->xmlFilePath;
    }

    /**
     * Получение данных из XML файла
     */
    public function getXmlData()
    {
        if (!file_exists($this->xmlFilePath)) {
            throw new Exception('XML файл отсутствует. Сначала выполните downloadXmlFile()');
        }
        
        ProcessLog::add("Парсинг XML файла: {$this->xmlFilePath}");
        
        $xmlContent = file_get_contents($this->xmlFilePath);
        
        if (empty($xmlContent)) {
            throw new Exception('XML файл пуст');
        }
        
        $dump = Convertor::xmlToArr($xmlContent);
        
        if (!$dump) {
            throw new Exception('Ошибка при парсинге XML файла');
        }
        
        if (!isset($dump['cruises']['cruise'])) {
            throw new Exception('Отсутствуют данные круизов в XML файле');
        }
        
        ProcessLog::add("XML файл успешно распарсен");
        
        return $dump;
    }

    /**
     * Получение пути к XML файлу
     */
    public function getXmlFilePath()
    {
        return $this->xmlFilePath;
    }

    /**
     * Проверка существования XML файла
     */
    public function xmlFileExists()
    {
        return file_exists($this->xmlFilePath);
    }

    /**
     * Очистка кеша XML (и legacy-файла для совместимости)
     */
    public function clearCache(): void
    {
        if (file_exists($this->xmlFilePath)) {
            @unlink($this->xmlFilePath);
        }
        if ($this->legacyXmlFilePath && file_exists($this->legacyXmlFilePath)) {
            @unlink($this->legacyXmlFilePath);
        }
    }
}

