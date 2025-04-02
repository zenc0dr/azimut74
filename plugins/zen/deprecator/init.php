<?php

if (!function_exists('deprecator')) {
    function deprecator(string $code = null): \Zen\Deprecator\Classes\Deprecator
    {
        return \Zen\Deprecator\Classes\Deprecator::getInstance()->code($code);
    }
}
