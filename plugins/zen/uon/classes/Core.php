<?php namespace Zen\Uon\Classes;

use Input;
use Zen\Cabox\Classes\Cabox;

class Core
{
    # Преобразовать массив в json-строку
    public function json($array, $return = false)
    {
        $json_string = json_encode($array, JSON_UNESCAPED_UNICODE);
        if ($return) {
            return $json_string;
        }
        echo $json_string;
    }

    public function api()
    {
        return new UonApi();
    }

    public function push($response)
    {
        if (is_array($response)) {
            $response = $this->json($response, true);
        }
        return response($response)->header('Access-Control-Allow-Origin', '*');
    }

    public function input($key, $options = null)
    {
        $value = Input::get($key);

        if (is_string($value)) {
            $value = trim($value);
        }

        if (is_string($options)) {
            $options = [$options];
        }

        if ($options) {
            foreach ($options as $option) {
                if ($option == 'only_digits') {
                    $value = preg_replace('/\D/', '', $value);
                }
                if ($option == 'bool') {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                }
                if ($option == 'int') {
                    $value = intval($value);
                }
            }
        }

        return $value;
    }

    # Создать объект Cabox-кэша
    public function cache($storage_code)
    {
        return new Cabox($storage_code);
    }
}
