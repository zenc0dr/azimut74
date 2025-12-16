<?php namespace Zen\Dolphin\Api;

use Zen\Dolphin\Classes\Core;

class Store extends Core
{
    # Список географических объектов
    # http://azimut74/zen/dolphin/api/store:geoTree?widget=ext // widget = ext || atm ...
    # http://azimut74/zen/dolphin/api/store:geoTree?widget=atm&date_of=01.03.2021&date_to=15.03.2021
    public function geoTree()
    {
        $options = [
            'type' => $this->input('widget'), # ext || atm
            'dates' => $this->input('dates'),
        ];

        $data = $this->store('GeoTree')->get($options);

        if (!$data) {
            $data = [];
        }

        $this->json($data);
    }

    # http://azimut74/zen/dolphin/api/store:getTours
    public function getTours()
    {
        $tours = $this->store('Extours')->getTours();
        $this->json(['tours' => $tours]);
    }

    # http://azimut74/zen/dolphin/api/store:openTour
    public function openTour()
    {
        $tour_id = $this->input('tour_id');
        $html = $this->store('Extours')->openTour($tour_id);
        $this->json(['html' => $html]);
    }
}
