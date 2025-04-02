<?php namespace Zen\Uongate\Classes;

use Zen\Cli\Classes\Cli;
use Zen\Uongate\Models\Settings;

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

    public static function fromJson(string $json)
    {
        return json_decode($json, true);
    }

    public static function store($store_name, $data = [])
    {
        return app("Zen\Uongate\Store\\$store_name")->get($data);
    }

    public static function setting($key, $default = null)
    {
        return Settings::get($key) ?? $default;
    }

    public static function artisanExec($artisan_command)
    {
        $cli = new Cli;
        $cli->nohup = false;
        $cli->artisanExec($artisan_command);
    }
}
