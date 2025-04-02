<?php namespace Zen\Putpics\Classes;


class Helpers
{
    public static function getSlicedPath($filename)
    {
        return implode('/', array_slice(str_split($filename, 3), 0, 3)) . '/' . $filename;
    }
}
