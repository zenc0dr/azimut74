<?php

namespace Mcmraak\Rivercrs\Patches;

use DB;

class DeckPricesPatch
{
    protected static ?self $instance = null;

    public function __construct()
    {
    }

    public static function getInstance(): self
    {
        return self::$instance ?? self::$instance = new self();
    }


    public function setPrice(int $checkin_id, int $deck_id, int $cabin_id, int $places_qnt, int $price)
    {
        DB::table('mcmraak_rivercrs_nprices')
            ->updateOrInsert(
                [
                    'checkin_id' => $checkin_id,
                    'deck_id' => $deck_id,
                    'cabin_id' => $cabin_id,
                    'places_qnt' => $places_qnt,
                    //'price' => $price,
                ],
                [
                    'checkin_id' => $checkin_id,
                    'deck_id' => $deck_id,
                    'cabin_id' => $cabin_id,
                    'places_qnt' => $places_qnt,
                    'price' => $price,
                ]
            );
    }

    public function getPrice(int $checkin_id, int $deck_id, int $cabin_id, int $places_qnt, int $original_price)
    {
        $price = DB::table('mcmraak_rivercrs_nprices')
            ->where([
                'checkin_id' => $checkin_id,
                'deck_id' => $deck_id,
                'cabin_id' => $cabin_id,
                'places_qnt' => $places_qnt,
            ])->first();

        return $price ? $price->price : $original_price;
    }

    public function getMinPrice(int $checkin_id): int
    {
        $price = DB::table('mcmraak_rivercrs_nprices')
            ->where('checkin_id', $checkin_id)
            ->first();

        return intval($price->price ?? 0);
    }
}
