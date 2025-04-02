<?php namespace Zen\Cabox\Classes;

use BackendAuth;
use Input;

class Core
{
    function input($key, $options = null)
    {
        $value = Input::get($key);

        if(is_string($value)) $value = trim($value);

        if(is_string($options)) {
            $options = [$options];
        }

        if($options) {
            foreach ($options as $option) {
                if($option == 'only_digits') {
                    $value = preg_replace('/\D/','', $value);
                }
                if($option == 'bool') {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                }
                if($option == 'int') {
                    $value = intval($value);
                }
            }
        }

        return $value;
    }

    function json($array, $return=false)
    {
        $json = json_encode($array, JSON_UNESCAPED_UNICODE);
        if($return) return $json;
        echo $json;
    }

    function isAdminCheck()
    {
        if(!BackendAuth::check()) die('Access denied');
    }

    function renderPath($path)
    {
        $path = str_replace(':storage', storage_path(), $path);
        $path = str_replace(':base', base_path(), $path);
        return $path;
    }

}
