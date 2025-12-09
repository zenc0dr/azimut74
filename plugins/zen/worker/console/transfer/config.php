<?php namespace Zen\Worker\Console\transfer;

use Exception;

/**
 * Конфигурация путей к базам данных SQLite для парсеров
 * 
 * Можно переопределить пути через переменные окружения в основном .env файле:
 * WATERWAY_PATH=storage/parsers_db/waterway_data.sqlite
 * GAMA_PATH=storage/parsers_db/gama_data.sqlite
 * и т.д.
 */
class TransferConfig
{
    /**
     * Пути по умолчанию (относительно base_path)
     * 
     * Базы данных монтируются из /Atman/projects/parsers/db/ 
     * в /var/www/html/storage/parsers_db/ внутри контейнера
     */
    private static $defaultPaths = [
        'waterway' => 'storage/parsers_db/waterway_data.sqlite',
        'gama' => 'storage/parsers_db/gama_data.sqlite',
        'germes' => 'storage/parsers_db/germes_data.sqlite',
        'infoflot' => 'storage/parsers_db/infoflot_data.sqlite',
        'volga' => 'storage/parsers_db/volga_data.sqlite',
    ];
    
    /**
     * Получить путь к базе данных для источника
     * 
     * @param string $source Имя источника (waterway, gama, germes, infoflot, volga)
     * @return string Абсолютный путь к файлу базы данных
     */
    public static function getDbPath($source)
    {
        // Пытаемся получить путь из переменной окружения
        $envKey = strtoupper($source) . '_PATH';
        $envPath = env($envKey);
        
        // Если переменная окружения не задана, используем путь по умолчанию
        if ($envPath) {
            $path = $envPath;
        } else {
            $path = self::$defaultPaths[$source] ?? null;
        }
        
        if (!$path) {
            throw new Exception("Не найден путь к базе данных для источника: {$source}");
        }
        
        // Формируем абсолютный путь
        return base_path($path);
    }
    
    /**
     * Получить все пути к базам данных
     * 
     * @return array Массив [source => absolute_path]
     */
    public static function getAllPaths()
    {
        $paths = [];
        foreach (array_keys(self::$defaultPaths) as $source) {
            $paths[$source] = self::getDbPath($source);
        }
        return $paths;
    }
}

