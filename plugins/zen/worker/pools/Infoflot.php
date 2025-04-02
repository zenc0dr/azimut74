<?php namespace Zen\Worker\Pools;

use Mcmraak\Rivercrs\Models\Cabins as Cabin;

class Infoflot extends RiverCrs
{
    public function getInfoflotWaybill($cruise)
    {
        $route = $cruise['route'];
        $route = explode(' – ', $route);

        if (isset($cruise['routeShort'])) {
            $route_short = $cruise['routeShort'];
            $route_short = explode(' – ', $route_short);
        } else {
            $route_short = [];
        }

        $waybill = [];
        $key = 0;
        $max = count($route) - 1;
        foreach ($route as $point) {
            $town_id = $this->getTownId($point, 'infoflot');
            $waybill[] = [
                'town' => $town_id,
                'excursion' => '',
                'bold' => ($key == 0 || $key == $max || in_array($point, $route_short)) ? 1 : 0
            ];
            $key++;
        }

        return $waybill;
    }

    public function sqlDate($date_string)
    {
        preg_match('/(\d+-\d+-\d+)/', $date_string, $matches);
        $Ymd = $matches[0];
        preg_match('/(\d+:\d+:\d+)/', $date_string, $matches);
        $His = $matches[0];
        return "$Ymd $His";
    }

    public function fillInfoflotPrices($prices, $checkin, $ship)
    {
        $cabins = [];
        foreach ($prices['prices'] as $type_id => $price) {
            foreach ($prices['cabins'] as $cabin) {
                if ($cabin['type_id'] == $type_id) {
                    $cabins[$price['type_name']] = [
                        'deck_id' => $this->getDeck($cabin['deck'])->id,
                        'places_main_count' => count($cabin['places']),
                        'price' => $price['prices']['main_bottom']['adult'],
                        #'desc' => $price['type_description']
                    ];
                    continue;
                }
            }
        }

        $del_q = [];
        $ins_q = [];

        foreach ($cabins as $cabin_name => $cabinData) {
            $cabin = Cabin::where('infoflot_name', $cabin_name)
                ->where('motorship_id', $ship->id)
                ->first();

            if (!$cabin) {
                $cabin = Cabin::where('category', $cabin_name)
                    ->where('motorship_id', $ship->id)
                    ->first();
            }

            if (!$cabin) {
                $cabin = new Cabin;
            }

            $cabin->motorship_id = $ship->id;
            $cabin->category = $cabin_name;
            $cabin->infoflot_name = $cabin_name;
            #$cabin->desc = $cabinData['desc'];
            $cabin->places_main_count = $cabinData['places_main_count'];
            $cabin->save();

            $this->deckPivotCheck($cabin->id, $cabinData['deck_id']);
            $queries = $this->updateCabinPrice($checkin->id, $cabin->id, (int)$cabinData['price'], null, 1);
            $del_q[] = $queries['del'];
            $ins_q[] = $queries['ins'];
        }


        $this->updateCabinPriceQueries($del_q, $ins_q);
    }
}
