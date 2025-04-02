<?php

if (!function_exists('fetcher')) {
    function fetcher(): \Zen\Fetcher\Classes\Fetcher
    {
        return \Zen\Fetcher\Classes\Fetcher::getInstance();
    }
}
