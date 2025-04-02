<?php namespace Zen\GroupTours\Classes;

use Zen\Dolphin\Classes\ValidatorHelper;
use Zen\Grinder\Classes\Grinder;
use Zen\GroupTours\Models\Settings;

/*
 * Core должен содержать только public static функции или private
 * Core НЕ должен содержать конструктор
*/

class Core
{
    public static function json($array, $return = false)
    {
        $json_string = json_encode($array, 256);
        if ($return) {
            return $json_string;
        }
        echo $json_string;
    }

    public static function store($store_name, $data = [])
    {
        return app("Zen\GroupTours\Store\\$store_name")->get($data);
    }

    public static function resize($image_path, $opts = [])
    {

        $thumb = Grinder::open($image_path);

        if (isset($opts['width'])) {
            $thumb = $thumb->width($opts['width']);
        }

        if (isset($opts['height'])) {
            $thumb = $thumb->height($opts['height']);
        }

        return $thumb->getThumb();
    }

    public static function setting($key, $default = null)
    {
        return Settings::get($key) ?? $default;
    }

    # Спец-класс-хелпер для упрощения валидации
    public static function validator()
    {
        return new ValidatorHelper;
    }
}
