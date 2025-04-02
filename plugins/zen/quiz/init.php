<?php

if (!function_exists('quiz')) {
    function quiz(): \Zen\Quiz\Classes\Quiz
    {
        return \Zen\Quiz\Classes\Quiz::getInstance();
    }
}
