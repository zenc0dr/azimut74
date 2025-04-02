<?php namespace Mcmraak\Rivercrs\Classes\Exist;

use Mcmraak\Rivercrs\Classes\Exist;
use Mcmraak\Rivercrs\Models\Cabins as Cabin;
use Mcmraak\Rivercrs\Models\Decks as Deck;
use Log;

class Germes extends Exist
{

    public $prices, $pivot, $cabins, $decks;
    public function loadCache()
    {
        $this->prices = $this->parser->cacheWarmUp(
            'germes-status',
            $this->query_type,
            ['id' => $this->checkin->eds_id])['Каюта'];
        ## dd($this->prices); // Cписок
        # "id" => "559"
        # "Статус" => "занята"  ||  "свободна"
        # "ЦенаОснМест" => "22400"
        # "ЦенаДопМест" => "11200"

        $this->pivot = $this->parser->cacheWarmUp(
            'germes-cabins-pivot',
            $this->query_type)['Kauta'];
        # dd($this->pivot);
        # @attributes
        #   "id_teplohod" => "49"
        #   "id" => "516"
        #   "idClassKauta" => "237"
        #   "number" => "307"

        $this->cabins = $this->parser->cacheWarmUp(
            'germes-cabins',
            $this->query_type)['Класс'];
        # dd($this->cabins);
        # @attributes
        #     "id" => "212"
        #     "id_teplohod" => "49"
        #     "КолвоОснМест" => "1"
        #     "КолвоДопМест" => "0"
        # "Название" => "1А(1)"
        # "Описание" => "Одноместная каюта....

        $this->decks = Deck::get();
    }

    public $query_type;
    public $test;
    public function getExist($checkin, $realtime)
    {
        $this->query_type = ($realtime)?'array_now':'array';

        #DEBUG $this->query_type = 'array';

        $this->checkin = $checkin;
        $this->loadCache();

        $rooms = [];

        foreach ($this->prices as $price) {
            $item = $this->getEdsCategory($price);
            if(!$item) continue; # Отсутствует связь
            if($price['Статус'] == 'занята') continue;

            $this->test = $item['category'];
            $deck_data = $this->getGermesDeck($item['desc']);

            if(isset($deck_data['id'])) {
                $record = $this->addRecord([
                    'deck_data' => $deck_data,
                    'cabin_name' => $item['category'],
                    'price_places' => $item['places'],
                    'price_value' => $item['price'],
                    'eds' => true
                ]);
            } else {
                foreach ($deck_data as $deck_data_item) {
                    $record = $this->addRecord([
                        'deck_data' => $deck_data_item,
                        'cabin_name' => $item['category'],
                        'price_places' => $item['places'],
                        'price_value' => $item['price'],
                        'eds' => true
                    ]);
                }
            }

            $rooms[] = [
                'n' => $item['num'],
                'd' => $record['deck_id'],
            ];
        }

        return [
            'decks' => $this->records,
            'rooms' => $rooms
        ];
    }

    public function getGermesDeck($desc_test)
    {
        if(is_array($desc_test)) {
            $desc_test = @$desc_test[0];
        }

        if(!$desc_test) {
            return [
                'id' => 8,
                'name' => 'Любая палуба',
            ];
        }

        $desc_test = mb_strtolower($desc_test);
        $desc_test = preg_replace('/ {1,}/',' ', $desc_test);

        $dt = explode(' ', $desc_test);

        for($i=0, $count = count($dt); $i<$count; $i++) {

            $word = trim($dt[$i]);
            if(!$word) continue;

            $prev_word  = (isset($dt[$i-1]))?trim($dt[$i-1]):false;
            $next_word  = (isset($dt[$i+1]))?trim($dt[$i+1]):false;
            $next2_word = (isset($dt[$i+2]))?trim($dt[$i+2]):false;
            $next3_word = (isset($dt[$i+3]))?trim($dt[$i+3]):false;

            // Слово вообще есть и перед ним не стоит 'и'
            $deck_id_name = $this->isDecsName($word);
            if($deck_id_name == false || $prev_word == 'и') continue;

            //после слова есть "палуба"
            if($this->isDescWord($next_word)) {
                return $deck_id_name;
            }

            //после слова стоит "и" а после и стоит имя палубы, а после имени палубы стоит "палуба"
            $deck_id_name_2 = $this->isDecsName($next2_word); // Слово после И
            if($next_word == 'и' && $deck_id_name_2 && $this->isDescWord($next3_word)) {
                return [
                    $deck_id_name, # Добавить палубу чьё имя перед союзом "и"
                    $deck_id_name_2, # Добавить палубу чьё имя после союза "и"
                ];
            }
        }

        return [
            'id' => 8,
            'name' => 'Любая палуба',
        ];
    }

    public function isDecsName($word)
    {
        if(mb_strlen($word) < 4) return false;
        $word = mb_substr($word, 0, 4);

        foreach ($this->decks as $deck) {
            $deck_name = mb_strtolower($deck->name);
            if(strpos($deck_name, $word) !== false) {

                if($deck->parent_id) {
                    $deck = $deck->parent;
                }

                return [
                    'id' => $deck->id,
                    'name' => $deck->name,
                ];
            }
        }
        return false;
    }

    public function isDescWord($word)
    {
        if(mb_substr($word, 0, 5) == 'палуб') {
            return true;
        } else {
            return false;
        }
    }

    public function getEdsCategory($price)
    {

        foreach ($this->pivot as $record)
        {
            if($record['@attributes']['id']==$price['id']) {
                $data = $this->getEdsCategoryItem($record['@attributes']['idClassKauta']);
                if(!$data['category']) return false;
                return [
                    'num' => $record['@attributes']['number'],
                    'category' => $data['category'],
                    'price' => intval($price['ЦенаОснМест']),
                    'places' => intval($data['places']),
                    'desc' => $data['desc'],
                ];
            }
        }
    }

    public function getEdsCategoryItem($class_id)
    {
        foreach ($this->cabins as $item) {
            if($item['@attributes']['id'] == $class_id) {
                return [
                    'category' => $item['Название'],
                    'places' => $item['@attributes']['КолвоОснМест'],
                    'desc' => $item['Описание'],
                ];
            }
        }
    }

}
