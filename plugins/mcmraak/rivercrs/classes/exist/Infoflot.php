<?php namespace Mcmraak\Rivercrs\Classes\Exist;

use Mcmraak\Rivercrs\Classes\Exist;
use Mcmraak\Rivercrs\Models\Cabins as Cabin;
use Mcmraak\Rivercrs\Models\Decks as Deck;
use Log;

class Infoflot extends Exist
{
    public $query_type;
    # Получить список круизов
    # http://azimut.dc/rivercrs/api/v2/parser/infoflotSeeder?id=init&debug=true

    # http://azimut.dc/rivercrs/api/v2/exist/34888?debug
    public function getExist($checkin, $realtime) {
        //dd($checkin, $realtime);

        $this->query_type = ($realtime)?'array_now':'array';
        $this->checkin = $checkin;

        //$ship_id = $checkin->motorship->infoflot_id;
        $cruise_id = $checkin->eds_id;
        //$exist_cabins = $this->parser->cacheWarmUp('cabins-status', $this->query_type, ['id' => "$ship_id:$cruise_id"]);
        $prices = $this->parser->cacheWarmUp('infoflot-cabins', 'array', ['id' => $cruise_id], 7, 0, 0);

        //dd($prices);

        $rooms = [];

        if(!isset($prices['cabins'])) return;

        foreach ($prices['cabins'] as $cabin) {

            $room = $cabin['name'];
            $room_status = $cabin['status'];


            $deck_name = $cabin['deck'];

            $cabinData = $this->getInfoflotCabinData($prices, $cabin);

            $record = $this->addRecord([
                'deck_name' => $deck_name,
                'cabin_name' => $cabinData['cabin_name'],
                'price_places' => $cabinData['price_places'],
                'price_value' => $cabinData['price_value'],
                'eds' => true
            ]);

            if($room_status == 0) {
                $rooms[] = [
                    'n' => $room,
                    'd' => $record['deck_id']
                ];
            }
        }

        /*
        foreach ($exist_cabins as $cabin)
        {
            $room = $cabin['name'];

            $deck_name = $cabin['deck'];
            $cabin_name = $cabin['type'];
            $price = $cabin['price'];
            $places_count = count($cabin['places']);

            $record = $this->addRecord([
                'deck_name' => $deck_name,
                'cabin_name' => $cabin_name,
                'price_places' => $places_count,
                'price_value' => $price,
                'eds' => true
            ]);

            $rooms[] = [
                'n' => $room,
                'd' => $record['deck_id']
            ];
        }
        */

        return [
            'decks' => $this->records,
            'rooms' => $rooms
        ];
    }

    function getInfoflotCabinData($prices, $cabin)
    {
        $price = $prices['prices'][$cabin['type_id']];
        return [
            'cabin_name' => $price['type_name'],
            'price_places' => count($cabin['places']),
            'price_value' => $price['prices']['main_bottom']['adult']
        ];
    }

}
