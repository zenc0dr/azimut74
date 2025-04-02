<?php namespace Mcmraak\Rivercrs\Classes;

class RivercrsShip
{
    public static function getShip($ship)
    {
        $data = [
            'meta_title' => $ship->metatitle,
            'meta_description' => $ship->metadesc,
            'meta_keywords' => $ship->metakey,
            'ship' => $ship,
            'ship_pic' => $ship->pic,
            'ship_name' => $ship->alt_name,
            'ship_images' => $ship->images->pluck('path')->toArray(),
            'ship_video' => $ship->youtube_link,
            'ship_desc' => $ship->desc,
            'ship_techs' => $ship->techs_arr,
            'ship_onboards' => $ship->onboards_arr,
            'ship_scheme' => $ship->scheme[0]->path ?? null,
            'ship_cabins' => $ship->decksWithCabins(),
            'ship_status' => $ship->status->name ?? null,
            'ship_status_desc' => $ship->status->desc ?? null,
            'permanent_discounts' => $ship->permanent_discounts,
            'temporary_discounts' => $ship->temporary_discounts,
            'price_contains' => $ship->add_a,
            'additionally_paid' => $ship->add_b,
        ];

        return $data;
    }
}
