<?php namespace Mcmraak\Rivercrs\Classes;

use Mcmraak\Rivercrs\Models\Settings;

class RivercrsSeoLinks
{
    public static function getSeoLinks()
    {
        return Settings::find(1)->relinks;
    }
}
