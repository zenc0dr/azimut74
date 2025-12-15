<?php namespace Zen\Worker\Console\infoflot;

use Exception;

/**
 * Класс для файлового кеширования ответов API Infoflot
 * Кеш вечный, удаляется только при явном вызове clear()
 */
class InfoflotCache
{
    private $cacheDir;

    public function __construct()
    {
        // Единый путь кеша: storage/parsers_cache/infoflot/
        // Legacy путь: storage/infoflot_cache/ — поддерживаем только как источник миграции.
        $preferred = storage_path('parsers_cache/infoflot');
        $legacy = storage_path('infoflot_cache');
        $this->cacheDir = $preferred;
        
        // Создаём директорию, если её нет
        if (!is_dir($this->cacheDir)) {
            if (!mkdir($this->cacheDir, 0775, true)) {
                throw new Exception("Не удалось создать директорию кеша: {$this->cacheDir}");
            }
        }

        // Если есть прогретый legacy-кеш и новый путь пуст — перенесём файлы.
        // Это защищает от ситуации, когда пользователь копировал кеш вручную/по SFTP.
        $this->migrateLegacyCacheIfNeeded($legacy, $preferred);
    }

    private function migrateLegacyCacheIfNeeded(string $legacy, string $preferred): void
    {
        // Новый кеш уже не пуст — миграция не нужна.
        $preferredHasFiles = is_dir($preferred) && count(glob($preferred . '/*.json')) > 0;
        if ($preferredHasFiles) {
            return;
        }

        // Старого кеша нет — миграция не нужна.
        $legacyHasFiles = is_dir($legacy) && count(glob($legacy . '/*.json')) > 0;
        if (!$legacyHasFiles) {
            return;
        }

        // Пытаемся перенести файлы "как есть" (best effort).
        foreach (glob($legacy . '/*.json') as $src) {
            $dst = $preferred . '/' . basename($src);
            if (!@rename($src, $dst)) {
                // Если rename не сработал (например, разные FS), пробуем copy+unlink
                if (@copy($src, $dst)) {
                    @unlink($src);
                }
            }
        }
    }

    /**
     * Получение данных из кеша
     * 
     * @param string $key Ключ кеша
     * @return mixed|null Данные из кеша или null, если не найдено
     */
    public function get($key)
    {
        $filePath = $this->getCachePath($key);
        
        if (!file_exists($filePath)) {
            return null;
        }
        
        try {
            $content = file_get_contents($filePath);
            if ($content === false) {
                return null;
            }
            
            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Если файл повреждён, удаляем его
                @unlink($filePath);
                return null;
            }
            
            return $data;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Сохранение данных в кеш
     * 
     * @param string $key Ключ кеша
     * @param mixed $data Данные для сохранения
     * @return bool Успешность операции
     */
    public function put($key, $data)
    {
        $filePath = $this->getCachePath($key);
        
        try {
            // Создаём директорию, если её нет
            $dir = dirname($filePath);
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0775, true)) {
                    throw new Exception("Не удалось создать директорию: {$dir}");
                }
            }
            
            $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            if ($json === false) {
                throw new Exception("Ошибка кодирования JSON: " . json_last_error_msg());
            }
            
            $result = file_put_contents($filePath, $json, LOCK_EX);
            if ($result === false) {
                throw new Exception("Не удалось записать файл кеша: {$filePath}");
            }
            
            // Устанавливаем права доступа
            chmod($filePath, 0664);
            
            // Пытаемся установить владельца (если запущено от root)
            if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
                // Если запущено от root, пытаемся установить владельца zen:zen
                $uid = posix_getpwnam('zen')['uid'] ?? null;
                $gid = posix_getgrnam('zen')['gid'] ?? null;
                if ($uid !== null && $gid !== null) {
                    chown($filePath, $uid);
                    chgrp($filePath, $gid);
                }
            }
            
            return true;
        } catch (Exception $e) {
            throw new Exception("Ошибка сохранения кеша: " . $e->getMessage());
        }
    }

    /**
     * Проверка наличия данных в кеше
     * 
     * @param string $key Ключ кеша
     * @return bool true если данные есть в кеше
     */
    public function has($key)
    {
        $filePath = $this->getCachePath($key);
        return file_exists($filePath) && is_readable($filePath);
    }

    /**
     * Очистка всего кеша
     * 
     * @return bool Успешность операции
     */
    public function clear()
    {
        if (!is_dir($this->cacheDir)) {
            return true; // Директории нет, значит кеш уже пуст
        }
        
        try {
            $files = glob($this->cacheDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                } elseif (is_dir($file)) {
                    $this->removeDirectory($file);
                }
            }
            return true;
        } catch (Exception $e) {
            throw new Exception("Ошибка очистки кеша: " . $e->getMessage());
        }
    }

    /**
     * Получение пути к файлу кеша
     * 
     * @param string $key Ключ кеша
     * @return string Путь к файлу
     */
    private function getCachePath($key)
    {
        // Используем hash для безопасности и уникальности имени файла
        $hash = md5($key);
        return $this->cacheDir . '/' . $hash . '.json';
    }

    /**
     * Рекурсивное удаление директории
     * 
     * @param string $dir Путь к директории
     * @return void
     */
    private function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}

