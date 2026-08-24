<?php namespace Mcmraak\Rivercrs\Classes\Exist;

use Mcmraak\Rivercrs\Classes\Exist;
use Mcmraak\Rivercrs\Models\Cabins as Cabin;
use Mcmraak\Rivercrs\Models\Decks as Deck;
use Log;

class Infoflot extends Exist
{
    public $query_type;
    # Получить список круизов
    # http://azimut74/rivercrs/api/v2/parser/infoflotSeeder?id=init&debug=true

    # http://azimut74/rivercrs/api/v2/exist/34888?debug
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

        # Каюты Infoflot продаются целиком (separate=0): при неполной загрузке
        # пассажир оплачивает свободные основные места. Раньше в таблицу попадало
        # число коек конкретной каюты при одной и той же main_bottom.adult —
        # из‑за этого 2/3/4-местное выглядело одинаково.
        $groups = [];

        foreach ($prices['cabins'] as $cabin) {
            if (!is_array($cabin)) {
                continue;
            }

            $cabinData = $this->getInfoflotCabinData($prices, $cabin);
            if (!$cabinData) {
                continue;
            }

            $deck_name = $cabin['deck'] ?? '';
            $group_key = $deck_name . '|' . $cabinData['cabin_name'];

            if (!isset($groups[$group_key])) {
                $groups[$group_key] = [
                    'deck_name' => $deck_name,
                    'cabin_name' => $cabinData['cabin_name'],
                    'adult' => $cabinData['price_value'],
                    'main_places' => 0,
                    'rooms' => [],
                ];
            }

            if ($cabinData['price_places'] > $groups[$group_key]['main_places']) {
                $groups[$group_key]['main_places'] = $cabinData['price_places'];
            }

            if (intval($cabin['status'] ?? -1) === 0) {
                $groups[$group_key]['rooms'][] = $cabin['name'];
            }
        }

        foreach ($groups as $group) {
            $record = null;
            foreach (self::occupancyPrices($group['main_places'], $group['adult']) as $row) {
                $record = $this->addRecord([
                    'deck_name' => $group['deck_name'],
                    'cabin_name' => $group['cabin_name'],
                    'price_places' => $row['price_places'],
                    'price_value' => $row['price_value'],
                    'eds' => true
                ]);
            }

            if (!$record) {
                continue;
            }

            foreach ($group['rooms'] as $room) {
                $rooms[] = [
                    'n' => $room,
                    'd' => $record['deck_id']
                ];
            }
        }

        return [
            'decks' => $this->records,
            'rooms' => $rooms
        ];
    }

    /**
     * Цены на 1 человека при размещении от полных основных мест вниз до 2
     * (одноместная категория — только 1). Свободные основные места оплачиваются
     * по тарифу взрослого, как в Infoflot /cruises/{id}/cabins/search.
     *
     * @return array<int, array{price_places:int, price_value:int}>
     */
    public static function occupancyPrices($mainPlaces, $adult)
    {
        $mainPlaces = intval($mainPlaces);
        $adult = intval($adult);
        if ($mainPlaces < 1 || $adult <= 0) {
            return [];
        }

        $minOcc = ($mainPlaces === 1) ? 1 : 2;
        if ($minOcc > $mainPlaces) {
            $minOcc = $mainPlaces;
        }

        $total = $mainPlaces * $adult;
        $prices = [];
        for ($occ = $mainPlaces; $occ >= $minOcc; $occ--) {
            $prices[] = [
                'price_places' => $occ,
                'price_value' => (int) round($total / $occ),
            ];
        }

        return $prices;
    }

    /**
     * Основные места — type=0 (нижние/верхние полки). Доп. места type=1
     * (диван в полулюксе) не входят в обязательную оплату каюты целиком.
     */
    public static function countMainPlaces($cabin)
    {
        $places = $cabin['places'] ?? [];
        if (!is_array($places) || !$places) {
            return 0;
        }

        $main = 0;
        $typed = false;
        foreach ($places as $place) {
            if (!is_array($place)) {
                continue;
            }
            if (array_key_exists('type', $place)) {
                $typed = true;
            }
            if (intval($place['type'] ?? 0) === 0) {
                $main++;
            }
        }

        if ($typed) {
            return $main;
        }

        return count($places);
    }

    function getInfoflotCabinData($prices, $cabin)
    {
        $typeId = $cabin['type_id'] ?? null;
        if ($typeId === null || !isset($prices['prices'][$typeId])) {
            return false;
        }

        $price = $prices['prices'][$typeId];
        $adult = $price['prices']['main_bottom']['adult'] ?? 0;
        if (intval($adult) <= 0) {
            return false;
        }

        return [
            'cabin_name' => $price['type_name'],
            'price_places' => self::countMainPlaces($cabin),
            'price_value' => intval($adult),
        ];
    }

}
