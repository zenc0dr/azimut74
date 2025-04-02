<?php namespace Zen\Worker\Pools;

use Mcmraak\Rivercrs\Models\Cabins as Cabin;
use Zen\Worker\Classes\Convertor;
use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use Exception;
use DB;

class VolgaCruises extends Volga
{
    function getCruises()
    {
        #$url_short = $this->stream->model->data['short_db'];
        #$url_full = $this->stream->model->data['full_db'];

        #$storage_path_short = storage_path('volga-db-short.php');
        #$storage_path_full = storage_path('volga-db.php');

        #exec("wget -O $storage_path_short '$url_short' >/dev/null");
        #exec("wget -O $storage_path_full '$url_full' >/dev/null");

        #$api_url = $this->stream->model->data['api_url'];
        $next_url = $this->stream->model->data['next_url'];
        #$api_xml = storage_path('api_url.xml');
        $next_xml = storage_path('next_url.xml');

        //exec("wget -O $api_xml '$api_url' >/dev/null");
        exec("wget -O $next_xml '$next_url' >/dev/null");

//        if (!file_exists($api_xml)) {
//            throw new Exception('Отсутствует файл круизов');
//        }

        if (!file_exists($next_xml)) {
            throw new Exception('Отсутствует файл круизов next');
        }

        //$dump = Convertor::xmlToArr(file_get_contents($api_xml));
        $next_dump = Convertor::xmlToArr(file_get_contents($next_xml));

        if (!isset($next_dump['cruises']['cruise'])) {
            throw new Exception('Отсутствуют данные круизов');
        }


//        if (!isset($dump['cruises']['cruise']) || !isset($next_dump['cruises']['cruise'])) {
//            throw new Exception('Отсутствуют данные круизов');
//        }

        #$merge_dump = $this->mergeDumps($dump, $next_dump);
        $merge_dump = $this->prepareDump($next_dump);

        foreach ($merge_dump['cruises'] as $cruise) {
            $cruise_id = $cruise['id'];
            if (!$cruise_id) {
                continue;
            }
            $this->stream->addJob('VolgaCruises@addCruise', ['id' => $cruise_id]);
        }
    }

    private function prepareDump($dump)
    {

        $merge = [
            'ships' => $this->handleItems('ships', 'ship', $dump),
            'classes' => $this->handleItems('classes', 'class', $dump),
            'decks' => $this->handleItems('decks', 'deck', $dump),
            'cabins' => $this->handleItems('cabins', 'cabin', $dump),
            'cruises' => $this->handleItems('cruises', 'cruise', $dump),
            'prices' => $this->handleItems('prices', 'price', $dump),
            'spos' => $this->handleItems('spos', 'spo', $dump)
        ];
        file_put_contents(storage_path('volga_dump.arr'), serialize($merge));
        return $merge;
    }

    private function handleItems($key_1, $key_2, $dump)
    {
        $output = [];
        $items = $dump[$key_1][$key_2] ?? null;
        if (!$items) {
            return [];
        }

        foreach ($items as $item) {
            $data = $item['@attributes'] ?? null;
            if (!$data) {
                $data = $item;
            }
            if ($key_2 === 'price' || $key_2 === 'spo') {
                $cruise_id = $data['cruise_id'] ?? null;
                $class_id = $data['class_id'] ?? null;
                $output["$cruise_id:$class_id"] = $data;
            } else {
                $output[$data['id']] = $data;
            }
        }

        return $output;
    }

    public function mergeDumps($dump_a, $dump_b)
    {
        $merge = [
            'ships' => $this->getItemsFromDump('ships', 'ship', $dump_a, $dump_b),
            'classes' => $this->getItemsFromDump('classes', 'class', $dump_a, $dump_b),
            'decks' => $this->getItemsFromDump('decks', 'deck', $dump_a, $dump_b),
            'cabins' => $this->getItemsFromDump('cabins', 'cabin', $dump_a, $dump_b),
            'cruises' => $this->getItemsFromDump('cruises', 'cruise', $dump_a, $dump_b),
            'prices' => $this->getItemsFromDump('prices', 'price', $dump_a, $dump_b),
            'spos' => $this->getItemsFromDump('spos', 'spo', $dump_a, $dump_b)
        ];
        file_put_contents(storage_path('volga_dump.arr'), serialize($merge));
        return $merge;
    }

    private function getDump()
    {
        return unserialize(file_get_contents(storage_path('volga_dump.arr')));
    }

    private function getItemsFromDump($key_1, $key_2, $dump_a, $dump_b)
    {
        $output = [];
        $items_1 = $dump_a[$key_1][$key_2];
        $items_2 = $dump_b[$key_1][$key_2];
        foreach ($items_1 as $item) {
            $data = $item['@attributes'] ?? null;
            if ($key_2 === 'price' || $key_2 === 'spo') {
                $output[$data['cruise_id'] . ':' . $data['class_id']] = $data;
            } else {
                $output[$data['id']] = $data;
            }
        }
        foreach ($items_2 as $item) {
            $data = $item['@attributes'] ?? null;
            if ($key_2 === 'price' || $key_2 === 'spo') {
                $output[$data['cruise_id'] . ':' . $data['class_id']] = $data;
            } else {
                $output[$data['id']] = $data;
            }
        }

        return $output;
    }

    public function checkDecks()
    {
        //$dump = Convertor::xmlToArr(file_get_contents(storage_path('volga-db.php')));
        $dump = $this->getDump();

        $decks_dump = $dump['decks'];
        foreach ($decks_dump as $volga_deck) {
            $volga_name = $volga_deck['name'];
            $deck =  DB::table('mcmraak_rivercrs_decks')->where('name', 'like', "%$volga_name%")->first();
            if (!$deck) {
                DB::table('mcmraak_rivercrs_decks')->insert([
                    'name' => $volga_name.' палуба',
                    'sort_order' => 10
                ]);
            }
        }
    }

    public function addCruise($data)
    {
        $id = $data['id'];
        $dump = $this->getDump();
        $volga_cruise = $dump['cruises'][$id];

        $checkin = Checkin::where('eds_code', 'volga')
            ->where('eds_id', $id)
            ->first();

        // $this->getVolgaShipName($volga_cruise['ship_id'], $dump);
        $ship_name =  $dump['ships'][$volga_cruise['ship_id']]['name'];

        $motorship = $this->getMotorship($ship_name, 'volga', $volga_cruise['ship_id']);

        if ($motorship === false) {
            return;
        }

        if (!$checkin) {
            $waybill = null;

            try {
                $waybill = $this->volgaWay($volga_cruise);
            } catch (Exception $exception) {
                // ...
            }

            if (!is_array($waybill) || count($waybill) < 2) {
                return;
            }


//            if (!$waybill) {
//                //throw new Exception('Ошибка при обработке круиза [Отсутсвует маршрут]:'.$id);
//                return;
//            }

            $date = $volga_cruise['begin_date'];
            $date = date('Y-m-d', strtotime($date));
            $date .= ' ' . $volga_cruise['begin_time'];
            $dateb = $volga_cruise['end_date'];
            $dateb = date('Y-m-d', strtotime($dateb));
            $dateb .= ' ' . $volga_cruise['end_time'];
            $this->daysDiffCheck($this->diffInIncompliteDays($date, $dateb), $id);
            $checkin = new Checkin;
            $checkin->date = $date;
            $checkin->dateb = $dateb;
            $checkin->motorship_id = $motorship->id;
            $checkin->active = 1;
            $checkin->eds_code = 'volga';
            $checkin->eds_id = (int) $id;
            $checkin->waybill_id = $waybill;
            $checkin->save();

        } else {
            $waybill = $this->volgaWay($volga_cruise);

            if (!is_array($waybill) || count($waybill) < 2) {
                return;
            }

            $checkin->waybill_id = $waybill;
            $checkin->save();
        }

        $this->fixCheckin($checkin->id);

        $prices = [];
        foreach ($dump['prices'] as $item) {
            $cruise_id = $item['cruise_id'];
            if ($cruise_id == $id) {
                if ($item['nofull'] == '0') {
                    $prices[] = [
                        'class_id' => $item['class_id'],
                        'price_value' => $item['price'],
                        'price2_value' => $this->getSPO($cruise_id, $item['class_id'], $dump)
                    ];
                }
            }
        }

        foreach ($prices as $price) {
            $price_value = intval($price['price_value']);
            $price2_value = intval($price['price2_value']);

            $volga_cabin_class = $this->getVolgaCabinClass($price, $dump);
            $volga_cabin_class_name = $volga_cabin_class['cabin_name'] ?? null;

            if (!$volga_cabin_class_name) {
                continue;
            }

            if ($this->isCabinNotLet($volga_cabin_class_name, $motorship->id)) {
                continue;
            }


            $volga_deck_name = $this->getVolgaDeckName($price, $dump);

            $deck = $this->getDeck($volga_deck_name);

            $cabin = Cabin::where('volga_name', $volga_cabin_class['cabin_name'])
                ->where('motorship_id', $motorship->id)
                ->first();

            if (!$cabin) {
                $cabin = Cabin::where('category', $volga_cabin_class['cabin_name'])
                    ->where('motorship_id', $motorship->id)
                    ->first();
            }

            if (!$cabin) {
                $cabin = new Cabin;
                $cabin->motorship_id = $motorship->id;
                $cabin->category = $volga_cabin_class['cabin_name'];
                $cabin->places_main_count = $volga_cabin_class['places_main_count'];
                $cabin->places_extra_count = $volga_cabin_class['places_extra_count'];
                $cabin->volga_name = $volga_cabin_class['cabin_name'];
                $cabin->desc = $volga_cabin_class['cabin_comment'];
                $cabin->save();
            }

            if ($deck) {
                $this->deckPivotCheck($cabin->id, $deck->id);
            }

            $this->updateCabinPrice($checkin->id, $cabin->id, $price_value, $price2_value);

//            $mem = master()->getSystemUsage()['mem_mb'];
//
//            echo "Память: " . $mem . "\n";
//            if ($mem > 500) {
//                die('Превышение памяти');
//            }
        }
    }
}
