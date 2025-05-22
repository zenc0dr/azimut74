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

        if (!$gama_route_data) {
            return null;
        }

        $navigation_id = $gama_route_data['Route']['@attributes']['navigation_id'];
        $cruise_data = $gama->getGamaFileData("navigation_{$navigation_id}_available.xml");

        $gama_ship_id = $cruise_data['Navigation']['@attributes']['ship_id'];

        if (isset($gama_route_data['Route']['CabinList']['Cabin']['@attributes'])) {
            $gama_route_data['Route']['CabinList']['Cabin'] = [
                $gama_route_data['Route']['CabinList']['Cabin']
            ];
        }

        foreach ($gama_route_data['Route']['CabinList']['Cabin'] as $cabin) {
            $gama_cabin_id = $cabin['@attributes']['id'];
            $gama_cabin_num = $cabin['@attributes']['name'];
            $places = $cabin['@attributes']['places'];

            foreach ($cabin['Cost'] as $cost) {
                if (isset($cost['@attributes'])) {
                    $cost = $cost['@attributes'];
                }

                if ($cost['persons'] !== $places) {
                    continue;
                }

                $category_data = $gama->getGamaCategory($gama_cabin_id, $gama_ship_id);
                $record = $this->addRecord([
                    'deck_name' => $category_data['deck_name'],
                    'cabin_name' => $category_data['name'],
                    'price_places' => intval($places),
                    'price_value' => $cost['std_3'],
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
