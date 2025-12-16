<?php namespace Mcmraak\Rivercrs\Classes;

use Ramsey\Uuid\Uuid;
use Cache;
use Zen\Worker\Classes\SearchCacheVersion;

class RivercrsApi
{
    private static function json($array)
    {
        echo json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    # http://azimut74/rivercrs/api/getToken
    public function getToken()
    {
        $token = Uuid::uuid4()->toString();
        Cache::put("$token.callback.token", true, 30);
        self::json([
            'success' => true,
            'token' => $token
        ]);
    }

    # http://azimut74/rivercrs/api/extraRefresh
    public function extraRefresh()
    {
        return SearchCacheVersion::get();
    }

    # http://azimut74/rivercrs/api/mounted
    public function mounted()
    {
        echo (new \Mcmraak\Rivercrs\Classes\Search)->mounted();
    }

    # http://azimut74/rivercrs/api/search
    public function search()
    {
        self::json([
            'items' => RivercrsSearch::search()
        ]);
    }

    # http://azimut74/rivercrs/api/ships
    public function ships()
    {
        self::json([
            'form' => RivercrsShips::getFormData()
        ]);
    }

    # http://azimut74/rivercrs/api/searchShips
    public function searchShips()
    {
        self::json(RivercrsShips::search());
    }

    # http://azimut74/rivercrs/api/booking
    public function booking()
    {
        app('Mcmraak\Rivercrs\Controllers\Booking')->sendBooking();
    }

    # http://azimut74/rivercrs/api/callback
    public function callback()
    {
        self::json(RivercrsCallback::send());
    }

    # http://azimut74/rivercrs/api/cabinInfo
    public function cabinInfo()
    {
        self::json([
            'html' => RivercrsCabin::getCabinInfo()
        ]);
    }

    # http://azimut74/rivercrs/api/openCabin
    public function openCabin()
    {
        self::json([
            'html' => RivercrsCabin::openCabin()
        ]);
    }
}
