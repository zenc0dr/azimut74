<?php namespace Mcmraak\Rivercrs\Classes\Exist;

use Mcmraak\Rivercrs\Models\Cabins as Cabin;
use Mcmraak\Rivercrs\Classes\Exist;
use Log;
use Zen\Worker\Pools\Waterway as WaterwayPool;

class Waterway extends Exist
{
    public $query_type;

    public function getExist($checkin, $realtime)
    {
        $this->query_type = ($realtime) ? 'array_now' : 'array';

        $this->checkin = $checkin;

        $ww_cruise_id = $this->checkin->eds_id;

        $ww = new WaterwayPool();
        $ww_rooms = $ww->wwQuery("json.v3.cabins?id=$ww_cruise_id", null, "waterway.cabins.$ww_cruise_id");

        $tariff_price2 = false;
        $rooms = [];

        foreach ($ww_rooms['result']['data'] as $room) {
            if (!$room['availability']) {
                continue;
            }

            $price2 = $room['minPrice']['discountedPrice'] ?? null;

            if ($price2) {
                $tariff_price2 = true;
            }

            $this->addRecord([
                'deck_name' => $room['deck']['name'],
                'cabin_name' => $room['class']['name'], # Имя каюты
                'price_places' => $this->getWwPlaces($room['class']['meta_name']), # Кол-во мест
                'price_value' => $room['minPrice']['basePrice'] / 100, # Цена 1
                'price2_value' => $price2, # Цена 2
                'eds' => true
            ]);
        }

        return [
            'decks' => $this->records,
            'rooms' => $rooms, # [['n'=> $n, 'd'=> $d],...] - Где n-номер каюты а d это id-палубы
            'tariff_price1_title' => [
                'name' => 'Базовый тариф<br>Руб. на 1 чел.',
                'desc' => '<b>Тариф Базовый.</b><br>Организация питания: завтрак, обед и ужин-буфет организованы по системе «шведский стол», свободная рассадка'
            ],
            'tariff_price2' => $tariff_price2,
            'tariff_price2_title' => [
                'name' => 'Расширенный тариф<br>Руб. на 1 чел.',
                'desc' => '<b>Тариф расширенный.</b><br>Организация питания:<br>▪ завтрак — буфет («шведский стол»);<br>▪ обед «Шеф-Меню» - заказная система (без включенных алкогольных напитков);<br>▪ ужин «Шеф-Меню» - заказная система с включенными напитками (вода, чай, кофе, на выбор: сок, вино красное/белое, пиво). Фиксированная рассадка, количество мест ограничено'
            ],
        ];
    }

    public function addWwRooms(&$rooms, $new_rooms, $deck_id)
    {
        $deck_id = intval($deck_id);
        foreach ($new_rooms as $room) {
            $room = "$room";
            $rooms[] = [
                'n' => $room,
                'd' => $deck_id
            ];
        }
    }

    public function getTariff($data)
    {
        foreach ($data['tariffs'] as $tariff) {
            if ($tariff['tariff_name'] == 'Тариф Взрослый') {
                return $tariff['prices'];
            }
        }
    }

    public function getTariffEx($data)
    {
        foreach ($data['tariffs'] as $tariff) {
            if ($tariff['tariff_name'] == 'Тариф Взрослый расширенный') {
                return $tariff['prices'];
            }
        }
    }

    public function getWwPlaces($string)
    {
        preg_match('/^(\d+)-/', $string, $matches);
        if (isset($matches[1])) {
            return intval($matches[1]);
        }
        return false;
    }

}
