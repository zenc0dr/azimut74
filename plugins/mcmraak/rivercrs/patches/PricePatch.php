<?php

namespace Mcmraak\Rivercrs\Patches;

use DB;

class PricePatch
{

    private static array $prices = [];
    private static array $keys = [];


    public static function addPrice(string $key, int $price)
    {
        self::$prices[$key] = $price;
    }

    public static function savePrices()
    {
        $insert = [];
        $keys = [];
        foreach (self::$prices as $key => $price) {
            $keys[] = $key;
            $insert[] = [
                'key' => $key,
                'price' => $price
            ];
        }
        DB::table('mcmraak_rivercrs_price_patch')
            ->whereIn('key', $keys)
            ->delete();

        DB::table('mcmraak_rivercrs_price_patch')
            ->insert($insert);
    }

    public static function addKey(string $key)
    {
        self::$keys[$key] = true;
    }

    public static function loadPrices()
    {
        $keys = array_keys(self::$keys);

        $prices = DB::table('mcmraak_rivercrs_price_patch')
            ->whereIn('key', $keys)->get();

        foreach ($prices as $price) {
            self::$prices[$price->key] = $price->price;
        }
    }

    public static function getPrice($key, $original_price)
    {
        if (isset(self::$prices[$key])) {
            return self::$prices[$key];
        }
        return $original_price;
    }
}
