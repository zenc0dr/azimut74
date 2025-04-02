<?php

if (!function_exists('forms')) {
    function forms(): \Zen\Forms\Classes\Forms
    {
        return \Zen\Forms\Classes\Forms::getInstance();
    }
}
