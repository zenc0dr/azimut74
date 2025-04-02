<?php

namespace Zen\Fetcher\Console;


class Dispatcher
{
    # http://zetaprint.vps/fetcher/api/streams.Dispatcher:run
    public function run()
    {
        fetcher()
            ->models()
            ->pool()
            ->active()
            ->get()
            ->each(function ($pool) {
                $pool->manager->run();
            });
    }
}
