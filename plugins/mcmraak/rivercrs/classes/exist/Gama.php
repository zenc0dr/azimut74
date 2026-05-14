<?php namespace Mcmraak\Rivercrs\Classes\Exist;

use Mcmraak\Rivercrs\Classes\Parser;
use Mcmraak\Rivercrs\Models\Cabins as Cabin;
use Mcmraak\Rivercrs\Classes\Exist;
use Log;

use Zen\Worker\Pools\GamaV2;

class Gama extends Exist
{
    public function getExist($checkin, $realtime): ?array
    {
        $gama = new GamaV2();
        $gama_route_data = $gama->getGamaRouteData($checkin->eds_id);
        $rooms = [];

        if (!$gama_route_data) {
            return null;
        }

        $navigation_id = $gama_route_data['Route']['@attributes']['navigation_id'];
        $cruise_data = $gama->getGamaFileData("navigation_{$navigation_id}_available.xml");

        $gama_ship_id = $cruise_data['Navigation']['@attributes']['ship_id'];

        $cabins = $gama_route_data['Route']['CabinList']['Cabin'] ?? [];
        if (isset($cabins['@attributes'])) {
            $cabins = [$cabins];
        }

        foreach ($cabins as $cabin) {
            $gama_cabin_id = $cabin['@attributes']['id'];
            $gama_cabin_num = $cabin['@attributes']['name'];
            $places = intval($cabin['@attributes']['places']);

            $costs = $cabin['Cost'] ?? [];
            if (isset($costs['@attributes'])) {
                $costs = [$costs];
            }

            foreach ($costs as $cost) {
                if (isset($cost['@attributes'])) {
                    $cost = $cost['@attributes'];
                }

                if (intval($cost['persons'] ?? 0) !== $places) {
                    continue;
                }

                $category_data = $gama->getGamaCategory($gama_cabin_id, $gama_ship_id);
                if (!$category_data) {
                    continue;
                }

                $record = $this->addRecord([
                    'deck_name' => $category_data['deck_name'],
                    'cabin_name' => $category_data['name'],
                    'price_places' => $places,
                    'price_value' => intval($cost['std_3'] ?? 0),
                    'eds' => true
                ]);

                $rooms[] = [
                    'n' => $gama_cabin_num,
                    'd' => $record['deck_id']
                ];
            }
        }

        return [
            'decks' => $this->records,
            'rooms' => $rooms
        ];
    }
}
