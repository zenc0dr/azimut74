<?php namespace Mcmraak\Rivercrs\Classes\Exist;

use Mcmraak\Rivercrs\Classes\Exist;
use Mcmraak\Rivercrs\Models\Cabins as Cabin;
use Log;

class Volga extends Exist
{
    public $dump, $classes;

    public $query_type;
    public function getExist($checkin, $realtime)
    {
        #$this->query_type = ($realtime)?'array_now':'array'; #TODO:НА время отключено
        $this->query_type = 'array';

        $this->dump = $this->parser->cacheWarmUp('volgawolga-database', $this->query_type);


        //dd($this->dump['free']['cruise'][0]);

        $cruise_id = $checkin->eds_id;
        $this->checkin = $checkin;

        # Каюты
        //dd($this->dump['cabins']['cabin'][0]['@attributes']['id']); # eds_id каюты
        //dd($this->dump['cabins']['cabin'][0]['@attributes']['number']); # Её номер
        //dd($this->dump['cabins']['cabin'][0]['@attributes']['class_id']); # eds_id Класса
        //dd($this->dump['cabins']['cabin'][0]['@attributes']['deck']); # eds_id палубы

        # Палубы
        //dd($this->dump['decks']['deck'][0]['@attributes']['id']); # eds_id палубы
        //dd($this->dump['decks']['deck'][0]['@attributes']['name']); # eds_name палубы

        # Классы
        //dd($this->dump['classes']['class'][0]['@attributes']['id']); # eds_id класса
        //dd($this->dump['classes']['class'][0]['@attributes']['name']); # eds-имя класса
        //dd($this->dump['classes']['class'][0]['@attributes']['m_count']); # Основных мест

        # Свободные каюты
        //dd($this->dump['free']['cruise'][0]['@attributes']['id']); # eds_id круиза
        //dd($this->dump['free']['cruise'][0]['cabin'][0]['@attributes']['id']); # eds_id кабин в круизе
        //dd($this->dump['free']['cruise'][0]['cabin'][0]['@attributes']['free']); # 0 || 1 = занято || свободно

        # Цены
        //dd($this->dump['prices']['price'][0]['@attributes']['cruise_id']); # eds_id круиза
        //dd($this->dump['prices']['price'][0]['@attributes']['class_id']); # eds_id класса
        //dd($this->dump['prices']['price'][0]['@attributes']['price']); # Цена

        ## Цитата из документации по источнику ВолгаWolga
        # free=0 говорит о том, что каюта пустая. free=1 - что там одно свободное место.
        # Во избежание проблем с полом и возрастом подселения рекомендую каюты с ненулевым
        # free считать занятыми.
        $rooms = [];
        $tariff_price2 = false;

        foreach ($this->dump['free']['cruise'] as $cruise) {
            if($cruise['@attributes']['id'] == $cruise_id) {
                if(!isset($cruise['cabin'])) continue;
                foreach ($cruise['cabin'] as $cabin) {
                    if($cabin['@attributes']['free'] == 1) continue; # Значит каюта уже занята
                    $eds_cabin_id = $cabin['@attributes']['id'];
                    $eds_item = $this->getEdsItem($eds_cabin_id, $cruise_id);
                    if(!$eds_item) continue;

                    //'price2_value' => $eds_item['price2_value'],

                    $record = $this->addRecord([
                        'deck_name' => $eds_item['deck_name'],
                        'cabin_name' => $eds_item['cabin_name'],
                        'price_places' => $eds_item['price_places'],
                        'price_value' => $eds_item['price_value'],
                        'eds' => true
                    ]);

                    if(@$eds_item['price2_value']) {
                        $record = $this->addRecord([
                            'deck_name' => $eds_item['deck_name'],
                            'cabin_name' => $eds_item['cabin_name'],
                            'price_places' => $eds_item['price_places'] - 1,
                            'price_value' => $eds_item['price2_value'],
                            'eds' => true
                        ]);
                    }


                    $rooms[] = [
                        'n' => $eds_item['num'],
                        'd' => $record['deck_id'],
                    ];
                }
            }
        }

        return [
            'decks' => $this->records,
            'rooms' => $rooms
        ];
    }

    public function getEdsItem($eds_cabin_id, $cruise_id)
    {
        foreach ($this->dump['cabins']['cabin'] as $cabin) {
            if($cabin['@attributes']['id'] == $eds_cabin_id) {

                $class_id = $cabin['@attributes']['class_id'];

                $eds_cabin_name = $this->getCabinName($class_id);
                if(!$eds_cabin_name) return false;

                $eds_deck_name = $this->getEdsDeckName($cabin['@attributes']['deck']);
                if(!$eds_deck_name) return false;

                $prices = $this->getPrice($cruise_id, $class_id);
                if(!$prices) return false;

                //if(count($prices) > 1) dd($prices);
                //return;

                $price = null;
                $price2 = null;

                foreach ($prices as $price_record) {
                    if($price_record['nofull'] == 0) {
                        $price = $price_record['price'];
                    }
                    if($price_record['nofull'] == 1) {
                        $price2 = $price_record['price'];
                    }
                }


                return [
                    'deck_name' => $eds_deck_name,
                    'cabin_name' => $eds_cabin_name['cabin_name'],
                    'price_places' => $eds_cabin_name['price_places'],
                    'price_value' => $price,
                    'price2_value' => $price2,
                    'num' => $cabin['@attributes']['number'],
                ];
            }
        }
        return false;
    }

    public function getCabinName($eds_cabin_id)
    {
        foreach ($this->dump['classes']['class'] as $class){
            if($class['@attributes']['id'] == $eds_cabin_id) {
                return [
                    'cabin_name' => $class['@attributes']['name'],
                    'price_places' => $class['@attributes']['m_count']
                ];
            }
        }
        return false;
    }

    public function getEdsDeckName($eds_deck_id)
    {
        foreach ($this->dump['decks']['deck'] as $deck) {
            if($deck['@attributes']['id'] == $eds_deck_id) {
                return $deck['@attributes']['name'];
            }
        }
        return false;
    }

    # Она возвращает первую попавшуюся, но их может быть и две
    public function getPrice($cruise_id, $class_id)
    {
        $prices = [];

        foreach ($this->dump['prices']['price'] as $price) {
            if($price['@attributes']['cruise_id'] == $cruise_id && $price['@attributes']['class_id'] == $class_id) {
                $prices[] = $price['@attributes'];
                //return intval($price['@attributes']['price']);
            }
        }

        if(!$prices) return false;
        return $prices;
    }

}
