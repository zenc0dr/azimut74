<?php

namespace Zen\Fetcher\Console;

class Stream
{
    public function run($input)
    {
        fetcher()->stream($input)->run();
    }
}
