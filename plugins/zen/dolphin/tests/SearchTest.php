<?php namespace Zen\Dolphin\Tests;

use Zen\Dolphin\Classes\Core;
use Zen\Cabox\Classes\Cabox;

class SearchTest
{
    private $core;

    function __construct()
    {
        $this->core = new Core;
    }

    function go()
    {
        $cache = new Cabox('dolphin.search');

        $query = [
            'date_a' => '27.04.2020',
            'date_b' => '30.04.2020',
            'nights' => [4],
            'areas' => [37787],
            'adults' => 2,
            'childrens' => []
        ];

        $stream_key = md5(serialize($query));

        $stream = [
            'stream_key' => $stream_key,
            'start_time' => time(),
            'query' => $query
        ];

        $cache->put($stream_key, $stream);

        //app('Zen\Dolphin\Classes\SearchStream')->run($stream_key);
    }
}
