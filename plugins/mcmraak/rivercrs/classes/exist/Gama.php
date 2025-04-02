<?php namespace Mcmraak\Rivercrs\Classes\Exist;

use Mcmraak\Rivercrs\Classes\Parser;
use Mcmraak\Rivercrs\Models\Cabins as Cabin;
use Mcmraak\Rivercrs\Classes\Exist;
use Log;

class Gama extends Exist
{

    public $query_type;

    public function getExist($checkin, $realtime)
    {
        $this->query_type = ($realtime)?'array_now':'array';

        $this->checkin = $checkin;
        $cruise = $this->parser->cacheWarmUp('gama-cruise', $this->query_type, ['id' => $checkin->eds_id]); // 14953

        if(!count($cruise['cabins'])) return;

        $rooms = [];

        foreach ($cruise['cabins']['cabin'] as $cabin) {

            $room_num = $this->getGamaParam($cabin, 'name');
            $eds_deck_id = $this->getGamaParam($cabin, 'deck');
            $eds_deck_name = $this->getGamaDeckName($eds_deck_id);

            $eds_cabin_category_name = $this->getGamaParam($cabin, 'category_name');
            $eds_cabin_category_id = $this->getGamaParam($cabin, 'category_iid');
            $eds_cabin_category_name .= '|'.$eds_cabin_category_id;

            if(!isset($cabin['cost'])) continue;

            foreach ($cabin['cost'] as $price) {
                $price_value = intval($this->getGamaParam($price, 'std3'));
                if($price_value) {
                    $price_places = intval($this->getGamaParam($price, 'inCabin'));


                    $record = $this->addRecord([
                        'deck_name' => $eds_deck_name,
                        'cabin_name' => $eds_cabin_category_name,
                        'price_places' => $price_places,
                        'price_value' => $price_value,
                        'eds' => true
                    ]);

                    $rooms[] = [
                        'n' => $room_num,
                        'd' => $record['deck_id']
                    ];
                }
            }
        }

        return [
            'decks' => $this->records,
            'rooms' => $rooms
        ];
    }

    public function getGamaDeckName($gama_deck_id) {
        $deck = $this->parser->cacheWarmUp('gama-deck', 'array', ['id' => $gama_deck_id]);
        if(!isset($deck['deck'])) return;
        return $this->getGamaParam($deck['deck'], 'name');
    }

    public function getGamaParam($arr, $param_name) {
        if (isset($arr['@attributes'][$param_name])) {
            return trim($arr['@attributes'][$param_name]);
        }
        if (isset($arr[$param_name])) {
            return trim($arr[$param_name]);
        }
        return false;
    }
}