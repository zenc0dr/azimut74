<?php namespace Zen\Worker\Classes;

set_time_limit(0);

class Convertor
{
    static function xmlToArr($xml)
    {
        $xml = @simplexml_load_string($xml);
        $json = json_encode($xml, JSON_UNESCAPED_UNICODE);
        return json_decode($json, 1);
    }
}
