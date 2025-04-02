<?php namespace Zen\Dolphin\Api;

use Zen\Dolphin\Classes\Core;

class AtmDebug extends Core
{
    # http://azimut.dc/zen/dolphin/api/AtmDebug:debugAtmDb
    function debugAtmDb()
    {
        $dates = ["18.06.2021", "21.06.2021"];
        $geo_objects = ["1:29", "1:77"];
        $query = [
            'adults' => 2,
            'childrens' => []
        ];

        $atm_db = $this->store('AtmSearch')->atmDb($dates, $geo_objects, $query);
        $this->ddd($atm_db);
    }
}
