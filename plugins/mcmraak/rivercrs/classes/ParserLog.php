<?php namespace Mcmraak\Rivercrs\Classes;

class ParserLog {

    static function getPath($eds_name)
    {
        return base_path('storage/rivercrs_cache/'.$eds_name.'_parser_errors.txt');
    }

    static function saveError($eds_name, $url, $text)
    {
        $path = self::getPath($eds_name);
        file_put_contents($path, "$url|$text\n", FILE_APPEND);
    }

    static function cleanErrors($eds_name)
    {
        $path = self::getPath($eds_name);
        @unlink($path);
    }
}