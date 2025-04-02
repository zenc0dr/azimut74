<?php namespace Zen\Dolphin\Parsers;

use Zen\Dolphin\Classes\Parser;
use Zen\Cabox\Models\Storage;
use Illuminate\Support\Facades\Artisan;

class TrashCleaner extends Parser
{
    function go()
    {
        $this->parser_class_name = 'TrashCleaner';
        $this->parser_name = 'Очистка мусора';
        $this->saveProgress('Очистка устаревшего HTTP кэша...');

        $time_now = time();

        $storage = Storage::whereCode('dolphin.parsers')->first();

        $storage->purge();

        $this->saveProgress('Очистка устаревшего потокового кэша...');

        $storage = Storage::whereCode('dolphin.search')->first();
        $storage->purge();

        $this->saveProgress('Очистка устаревшего сервисного кэша...');

        $cache = $this->cache('dolphin.service');

        $max_time_diff = 15552000; // полгода (60×60×24×30×6)

        $cache->handleItems(function ($item) use ($time_now, $max_time_diff, $cache) {
            $time = $item['time'];
            $key = $item['key'];
            if($time_now - $time > $max_time_diff) $cache->del($key);
        });

        $this->parserSuccess();
    }
}
