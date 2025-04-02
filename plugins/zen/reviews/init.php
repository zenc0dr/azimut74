<?php

if (!function_exists('reviews')) {
    function reviews(): \Zen\Reviews\Classes\Reviews
    {
        return \Zen\Reviews\Classes\Reviews::getInstance();
    }
}
