<?php namespace Zen\Worker\Pools;

use Exception;
use Zen\Worker\Classes\Convertor;

class Volga extends RiverCrs
{
    public function getVolgaCruise($id, $dump_cruises)
    {
        foreach ($dump_cruises as $item) {
            if ($item['@attributes']['id'] === $id) {
                return $item['@attributes'];
            }
        }
        $message = "volga: Не найден круиз #$id";
        throw new Exception($message);
    }

    public function findAttr($array, $attr_name)
    {
        //$deck_id = $array['@attributes'][$attr_name];
        if (isset($array['@attributes'][$attr_name])) {
            return $array['@attributes'][$attr_name];
        }
        if (isset($array[$attr_name])) {
            return $array[$attr_name];
        }
        return false;
    }

    public function getVolgaShipName($ship_id, $dump)
    {
        foreach ($dump['ships']['ship'] as $ship) {
            $test_ship_id = $this->findAttr($ship, 'id');
            if ($test_ship_id == $ship_id) {
                return $this->findAttr($ship, 'name');
            }
        }

        $message = "volga: Не найден теплоход #$ship_id";
        throw new Exception($message);
    }

    public function volgaWay($volga_cruise)
    {
        $waybill_string = $volga_cruise['route'];

        $way = $this->checkSeparator($waybill_string);
        $way = explode(' - ', $way);
        $waybill = [];
        $key = 0;
        foreach ($way as $route) {
            $town_id = $this->getTownId($route, 'volga');
            $waybill[] = [
                'town' => $town_id,
                'excursion' => '',
                'bold' => 0,
            ];
            $key++;
        }
        return $waybill;
    }

    public function volgaWayBolder($volga_cruise)
    {
        $dump = Convertor::xmlToArr(file_get_contents(storage_path('volga-db-short.php')));

        $volga_cruise_id = $volga_cruise['id'];

        $short_way = null;
        foreach ($dump['cruises']['cruise'] as $cruise) {
            $id = $cruise['@attributes']['id'];
            if ($volga_cruise_id == $id) {
                $short_way = $cruise['@attributes']['route'];
            }
        }

        $long_route = $this->checkSeparator($volga_cruise['route']);
        $short_way = $this->checkSeparator($short_way);

        $long_way = explode('-', $long_route);
        $short_way = explode('-', $short_way);

        $bold = [];
        foreach ($short_way as $route) {
            $bold[] = $this->getTownId($route, 'volga');
        }

        $waybill = [];
        $key = 0;
        $last = count($long_way) - 1;
        foreach ($long_way as $route) {
            $town_id = $this->getTownId($route, 'volga');
            $waybill[] = [
                'town' => $town_id,
                'excursion' => '',
                'bold' => (in_array($town_id, $bold) || $key == 0 || $key == $last) ? 1 : 0,
            ];
            $key++;
        }
        return $waybill;
    }

    public function getSPO($cruise_id, $class_id, $dump)
    {
        if (!@$dump['spos']) {
            return null;
        }
        foreach ($dump['spos'] as $spo) {
            if ($spo['cruise_id'] == $cruise_id && $spo['class_id'] == $class_id) {
                return $spo['spo'];
            }
        }
        return null;
    }

    public function getVolgaCabinClass($price, $dump)
    {
        foreach ($dump['classes'] as $item) {
            if ($item['id'] == $price['class_id']) {
                return [
                    'cabin_name' => $item['name'],
                    'cabin_comment' => $item['comment'],
                    'places_main_count' => $item['m_count'],
                    'places_extra_count' => $item['r_count'],
                ];
            }
        }
    }

    public function getVolgaDeckName($price, $dump)
    {
        $deck_id = false;
        foreach ($dump['cabins'] as $item) {
            $class_id = $item['class_id'];
            if ($class_id == $price['class_id']) {
                $deck_id = $item['deck'];
            }
        }

        if ($deck_id) {
            return $dump['decks'][$deck_id]['name'];
        }

        return false;
    }
}
