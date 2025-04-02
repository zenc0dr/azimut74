<?php namespace Zen\Worker\Pools;

use Mcmraak\Rivercrs\Classes\CacheSettings;
use Mcmraak\Rivercrs\Models\Motorships as Ship;

class WaterwayShips extends Waterway
{
    public function getShips()
    {
        $response = $this->wwQuery('json.v3.motorships?limit=100');

        $count = intval($response['result']['count']);
        if (!$count) {
            throw new Exception('error ww2');
        }

        if ($count > 100) {
            $response = $this->wwQuery('json.v3.motorships?limit=' . $count);
        }

        $ships = @$response['result']['data'];

        foreach ($ships as $ship) {
            if (CacheSettings::shipIsBad($ship['name'], 'waterway')) {
                echo 'Теплоход' . $ship['name'] . " проигнорирован так как добавлен в исключения (Водоход)\n";
                continue;
            }

            $this->stream->addJob('WaterwayShips@addShip', $ship);
        }
    }

    public function addShip($ship)
    {
        $waterway_id = $ship['id'];
        $name = $ship['name'];
        $desc = $ship['description'];

        $ship = Ship::where('waterway_id', $waterway_id)->first();
        if ($ship) {
            return;
        }

        $ship = Ship::where('name', 'like', "%$name%")->first();

        if (!$ship) {
            $ship = new Ship;
            $ship->name = $name;
            $ship->desc = $desc;
            $ship->add_a = '';
            $ship->add_b = '';
            $ship->booking_discounts = '';
            $ship->social_discounts = '';
            $ship->youtube = '';
            $ship->banner = '';
            $ship->techs = [];
        }
        $ship->waterway_id = $waterway_id;
        $ship->save();
    }
}
