<?php namespace Zen\Dolphin\Api;

use Zen\Dolphin\Classes\Core;
use Zen\Dolphin\Models\Link;
use DB;

class Atm extends Core
{
    # http://azimut.dc/zen/dolphin/api/atm:allowedDates
    public function allowedDates()
    {
        DB::unprepared("SET sql_mode = ''");
        $allowed_dates = $this->store('AtmSearch')->allowedDates();
        $this->json($allowed_dates);
    }

    # http://azimut.dc/zen/dolphin/api/atm:allowedHotels
    # @input: dates (array), geo_objects (array)
    public function allowedHotels()
    {
        $dates = $this->input('dates');
        $geo_objects = $this->input('geo_objects');

        if (!$dates || !$geo_objects) {
            $this->json([]);
            return;
        };

        $allowed_hotels = $this->store('AtmSearch')->allowedHotels($dates, $geo_objects);
        $this->json($allowed_hotels);
    }

    # http://azimut.dc/zen/dolphin/api/atm:db
    # @input: dates (array), hotels (array)
    public function db()
    {
        #sleep(3);
        $dates = $this->input('dates');
        $geo_objects = $this->input('geo_objects');
        $query = $this->input('query');

        if (!$dates || !$geo_objects) {
            return;
        }

        $atm_db = $this->store('AtmSearch')->atmDb($dates, $geo_objects, $query);
        $this->json($atm_db);
    }

    public function query()
    {
        $query = [
            'dates' => $this->input('dates'),
            'tours' => $this->input('tours'),
            'adults' => $this->input('adults'),
            'childrens' => $this->input('childrens'), // Массив возрастов
        ];

        $results = $this->store('AtmSearch')->query($query);
        $this->json($results);
    }

    public function makeLink()
    {
        $link_set = $this->input('link_set');
        $key = Link::set($link_set);
        $this->json([
            'link' => $key
        ]);
    }

    public function getLinkData()
    {
        $key = $this->input('key');
        $data = Link::get($key);
        $this->json($data);
    }

    # http://azimut.dc/zen/dolphin/api/atm:getHotelPreset?hotel_id=2459
    public function getHotelPreset()
    {
        # Надо получить дату и гео-точку
        $hotel_id = $this->input('hotel_id');
        $hotel = $this->model('Hotel')->find($hotel_id);

        $pivot = DB::table('zen_dolphin_tarrifs as tarrif')
            ->where('tarrif.hotel_id', $hotel_id)
            ->where('price.date', '>', date('Y-m-d 00:00:00'))
            ->where('tour.active', 1)
            ->where('price.azroom_id', '>', 1)
            ->join('zen_dolphin_tours as tour', 'tour.id', '=', 'tarrif.tour_id')
            ->join('zen_dolphin_prices as price', 'price.tarrif_id', '=', 'tarrif.id')
            ->select(
                'price.date as date'
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        if (!$pivot->count()) {
            return;
        }

        $this->json([
            'geo_objects' => [$hotel->geo_code],
            'dates' => [$this->dateFromMysql($pivot[0]->date)]
        ]);
    }

    # http://azimut.dc/zen/dolphin/api/atm:openGPS?gps=44.880857:37.313113&html=true
    public function openGPS()
    {
        $gps = $this->input('gps'); # ex: 44.880857:37.313113
        $html = $this->input('html');

        $cache = $this->cache('atm.maps');

        $map = $cache->get('map.'.$gps);

        if (!$map) {
            $hotel = $this->model('Hotel')->where('gps', $gps)->first();

            if (!$hotel) {
                return;
            }

            $map = $this->store('Map')->byHotel($hotel);
            $cache->put('map.'.$gps, $map);
        }

        if ($html) {
            echo $map;
        } else {
            $this->json([
                'map' => $map
            ]);
        }
    }
}
