<?php namespace Zen\Worker\Classes;

class SearchCacheVersion
{
    /**
     * Путь к файлу с версией поискового кеша.
     *
     * @return string
     */
    protected static function getFilePath()
    {
        // Файл расположен в корне OctoberCMS рядом с приложением
        return base_path('crs_extra_refresh');
    }

    /**
     * Получить текущую версию поискового кеша.
     * Если файл отсутствует или значение некорректно, возвращает 0.
     *
     * @return int
     */
    public static function get()
    {
        $path = static::getFilePath();

        if (!is_file($path)) {
            return 0;
        }

        try {
            $contents = @file_get_contents($path);
        } catch (\Exception $e) {
            return 0;
        }

        if ($contents === false) {
            return 0;
        }

        $contents = trim($contents);

        if ($contents === '') {
            return 0;
        }

        if (!preg_match('/^-?\d+$/', $contents)) {
            return 0;
        }

        return (int) $contents;
    }

    /**
     * Установить конкретное значение версии поискового кеша.
     *
     * @param int $value
     * @return bool Успешность записи
     */
    public static function set($value)
    {
        $path = static::getFilePath();

        $dir = dirname($path);
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
                return false;
            }
        }

        $value = (int) $value;

        try {
            return @file_put_contents($path, (string) $value) !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Увеличить версию поискового кеша на 1.
     * Если файл отсутствует, создаёт его со значением 1.
     *
     * @return int Новое значение версии
     */
    public static function increment()
    {
        $current = static::get();

        // Если файла не было или он некорректен, начинаем с 0 и делаем 1
        $new = $current + 1;

        if (!static::set($new)) {
            // В случае неудачной записи просто возвращаем старое значение
            return $current;
        }

        return $new;
    }
}

