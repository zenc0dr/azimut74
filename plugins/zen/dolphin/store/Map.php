<?php namespace Zen\Dolphin\Store;

use Zen\Dolphin\Classes\Core;
use Zen\Dolphin\Models\Hotel;
use View;

class Map extends Core
{
    function byHotel(Hotel $main_hotel)
    {
        $zoom = 15;
        $hotels = null;

        if($main_hotel->city_id) {
            $hotels = Hotel::whereNotNull('gps')->where('city_id', $main_hotel->city_id)->get();
        } elseif($main_hotel->region_id) {
            $hotels = Hotel::whereNotNull('gps')->whereNull('city_id')->where('region_id', $main_hotel->region_id)->get();
        }

        if(!$hotels) return;

        $points = [];

        $i = 0;

        $main_point_id = null;

        foreach ($hotels as $hotel) {

            if(!$hotel->gps) continue;

            if($hotel->id == $main_hotel->id)  $main_point_id = $i;

            $gps = explode(':', $hotel->gps);
            $points[] = [
                'type' => 'Feature',
                'id' => $i,
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [floatval($gps[0]), floatval($gps[1])],
                ],
                'properties' => [
                    'iconContent' => ($hotel->id == $main_hotel->id) ? "<b>{$hotel->name}</b>" : $hotel->name ,
                    'balloonContentHeader' => "",
                    'balloonContentBody' => View::make('zen.dolphin::store.map.map_baloon', ['hotel' => $hotel])->render(),
                    'balloonContentFooter' => '',
                    'clusterCaption' => $hotel->name,
                    'hintContent' => '',
                ],
                'options' => [
                    'preset' => ($hotel->id == $main_hotel->id) ?'islands#redStretchyIcon' : ''
                ]
            ];

            $i++;
        }

        $points = json_encode($points, JSON_UNESCAPED_UNICODE);

        return View::make('zen.dolphin::store.map.yandex_map', [
            'points' => $points,
            'start_gps' => explode(':', $main_hotel->gps),
            'main_point_id' => $main_point_id,
            'zoom' => $zoom
        ])->render();
    }
}
