<?php namespace Zen\Uon\Api;

use Zen\Uon\Classes\Core;
use Cache;

class Debug extends Core {


    # http://azimut74/zen/uon/api/debug:go
    function go()
    {
        //Cache::add('olo', 'value', 60);
        dd(
            Cache::get('olo')
        );
        //$this->api()->getRequest(15638)->dump();
    }

    # http://azimut74/zen/uon/api/debug:testConnection
    function testConnection()
    {
        $query = 'https://api.u-on.ru/F3wgG39D0o9PQ3brIjYs1776930457/user/17677.json';

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $query,
            CURLOPT_POST => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false
        ));
        $resp = curl_exec($curl);
        curl_close($curl);

        $response = json_decode($resp, 1);

        $response = json_encode($response, JSON_UNESCAPED_UNICODE);
        //echo $response;

        return response($response)->header('Access-Control-Allow-Origin', '*');

        //dd($response);
    }

    # http://azimut74/zen/uon/api/debug:testQuery
    function testQuery()
    {
        $request_id = 17535;
        $request = $this->api()->getRequest($request_id)->getArray();
        $tourists = $request['tourists'];
        dd($request, $tourists);
    }
}

// https://api.u-on.ru/F3wgG39D0o9PQ3brIjYs1776930457/countries.json
// https://api.u-on.ru/F3wgG39D0o9PQ3brIjYs1776930457/reminder/14973.json

// Данные по заявке: https://api.u-on.ru/F3wgG39D0o9PQ3brIjYs1776930457/request/14973.json
//Данные по туристу: https://api.u-on.ru/F3wgG39D0o9PQ3brIjYs1776930457/user/17677.json

// https://api.u-on.ru/{key}/service_type.{_format}

// https://api.u-on.ru/F3wgG39D0o9PQ3brIjYs1776930457/request-by-client/9656/1.json
