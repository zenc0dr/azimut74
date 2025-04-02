<?php namespace Zen\Dolphin\Store;

use Zen\Dolphin\Classes\Core;

class Extours extends Core
{
    function getTours()
    {
        $cache = $this->cache('dolphin.parsers');
        $tours = [];
        $cache->handleItems(function ($data) use (&$tours) {
            if(strpos($data['key'], 'dolpin.tour.id') !== 0) return;
            $value = $data['value']['response'];
            if(!@$value['Name']) return;
            $tour_name = $value['Name'];
            $tours[] = [
                'id' => $value['Id'],
                'name' => $tour_name,
            ];
        });

        return $tours;
    }

    function openTour($tour_id)
    {
        $cache = $this->cache('dolphin.parsers');
        $tour = $cache->get("dolpin.tour.id#$tour_id");
        if($tour) {
            $tour = $tour['response'];
            $this->addHotels($tour, $cache);
            $dump = $this->htmlDump($tour);
        } else {
            $dump = 'Тур не обнаружен в кэше';
        }

        return $this->modal($dump, @$tour['Name']);
    }

    private function addHotels(&$tour, $cache)
    {
        foreach ($tour['Hotels'] as &$hotel) {
            $hotel = $cache->get("dolpin.hotel.id#$hotel")['response'];
        }
    }
}
