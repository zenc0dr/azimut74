<?php

if (!function_exists('master')) {
    function master(): \Zen\Master\Classes\Master
    {
        return \Zen\Master\Classes\Master::getInstance();
    }
}
