<?php namespace Zen\Keeper\Classes;

use Request;

class Core
{
    public function json($array, $return = false)
    {
        if ($return == 'dd') {
            dd($array);
        }
        $json = json_encode($array, JSON_UNESCAPED_UNICODE);
        if ($return) {
            return $json;
        }
        echo $json;
    }

    public function getDomain()
    {
        $domain = env('APP_URL');

        if (substr($domain, -1) == '/') {
            $domain = substr($domain, 0, -1);
        }

        return $domain;
    }

    public static function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' Gb';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' Mb';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' Kb';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }
}
