<?php namespace Mcmraak\Rivercrs\Classes;

use Ramsey\Uuid\Uuid;
use Cache;

class RivercrsApi
{
    private static function json($array)
    {
        echo json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    # http://azimut.dc/rivercrs/api/getToken
    public function getToken()
    {
        $token = Uuid::uuid4()->toString();
        Cache::put("$token.callback.token", true, 30);
        self::json([
            'success' => true,
            'token' => $token
        ]);
    }

    # http://azimut.dc/rivercrs/api/extraRefresh
    public function extraRefresh()
    {
        return env('CRS_EXTRA_REFRESH');
    }

    # http://azimut.dc/rivercrs/api/mounted
    public function mounted()
    {
        echo (new \Mcmraak\Rivercrs\Classes\Search)->mounted();
    }

    # http://azimut.dc/rivercrs/api/search
    public function search()
    {
        self::json([
            'items' => RivercrsSearch::search()
        ]);
    }

    # http://azimut.dc/rivercrs/api/ships
    public function ships()
    {
        self::json([
            'form' => RivercrsShips::getFormData()
        ]);
    }

    # http://azimut.dc/rivercrs/api/searchShips
    public function searchShips()
    {
        self::json(RivercrsShips::search());
    }

    # http://azimut.dc/rivercrs/api/booking
    public function booking()
    {
        app('Mcmraak\Rivercrs\Controllers\Booking')->sendBooking();
    }

    # http://azimut.dc/rivercrs/api/callback
    public function callback()
    {
        self::json(RivercrsCallback::send());
    }

    # http://azimut.dc/rivercrs/api/cabinInfo
    public function cabinInfo()
    {
        self::json([
            'html' => RivercrsCabin::getCabinInfo()
        ]);
    }

    # http://azimut.dc/rivercrs/api/openCabin
    public function openCabin()
    {
        self::json([
            'html' => RivercrsCabin::openCabin()
        ]);
    }
}
