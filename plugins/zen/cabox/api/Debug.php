<?php namespace Zen\Cabox\Api;

use Zen\Dolphin\Classes\Core;
use Zen\Cabox\Models\Storage;
use Zen\Cabox\Classes\Cabox;

class Debug
{
    # http://azimut.dc/zen/cabox/api/Debug@testConfig
    function testConfig()
    {
        $cache = new Cabox('dolphin.parsers');
        dd($cache->storageSize());
    }

    # http://baby.zen/zen/cabox/api/Debug@openDump?
    function openDump()
    {
        app('Zen\Cabox\Controllers\Storages')->onShowDump();
    }

    # http://azimut.dc/zen/cabox/api/Debug@testHandlers
    function testHandlers()
    {
        $cabox = new Cabox('dolphin.parsers');


        $output = 0;

        $cabox->handleItems(function ($item) use (&$output) {
            $time = $item['time'];
            $value = $item['value'];
            $key = $item['key'];

            if(strpos($key, 'dolpin.tour.id#') === 0) $output++;
        });


        dd($output);
    }
}
