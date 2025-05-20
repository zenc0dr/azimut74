<?php namespace Mcmraak\Rivercrs\Classes;

use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use App;
use ToughDeveloper\ImageResizer\Classes\Image as Resizer;
use October\Rain\Support\Collection;
use Mcmraak\Rivercrs\Classes\Parser;
use Mcmraak\Rivercrs\Models\Decks as Deck;
use Mcmraak\Rivercrs\Models\Cabins as Cabin;
use Mcmraak\Rivercrs\Models\Log as JLog;
use Input;
use DB;
use Log;
use Exception;

use Cache;
use Zen\Cabox\Classes\Cabox;
use Mcmraak\Rivercrs\Patches\PricePatch;

class Exist
{
    public
        $parser,
        $checkin,
        $records = [],
        $deck_cache = [],
        $cabins_cache = [];

    public static $static_checkin;


    public $exist_data, $exist_mix;


    public function __construct()
    {
        $this->parser = new Parser;
    }


    public function json($array)
    {
        echo json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function error($message)
    {
        $this->json([
            'error' => $message
        ]);
    }

    # Возвращает массив данных о незанятых каютах
    /*
        decks (array)
            id (int) - id палубы
            name (string) - Имя палубы
            cabins (array)
                id (int) - id категории кают
                name (string) - Название категории кают
                main_places (int) - Количество основных мест
                extra_places (int) - Количество дополнительных мест
                prices (array)
                    price_places (int) - Количество мест размещения
                    price_value (int) - Цена
        rooms (array)
            n (string) - Номер каюты
            c (int) - cabin_id - id категории кают
            w (int) - Ширина
            h (int) - Высота
            x (int) - Ось X
            y (int) - Ось Y
            f (int[0|1]) - [Занято|Свободно]
            d (int) - deck_id - id палубы
    */
    public function get($checkin_id, $type = 'json', $realtime = true)
    {
        $checkin = null;
        $cabox = null;

        if (is_object($checkin_id)) {
            $checkin = $checkin_id;
            $checkin_id = $checkin->id;
        }

        if ($type === 'array') {
            $cabox = new Cabox('rivercrs');
            $array_cache_key = "exist_array:$checkin_id";
            $cached_data = $cabox->get($array_cache_key, 900);
            if ($cached_data) {
                return $cached_data;
            }
        }

        # Схема выводимых данных: http://azimut.dc/plugins/mcmraak/rivercrs/assets/images/docs/data_sheme.jpg

        #API_URL: http://azimut.dc/rivercrs/api/v2/exist/8784?debug     :Gama # Никитин
        #API_URL: http://azimut.dc/rivercrs/api/v2/exist/1550306?debug   :Waterway
        #API_URL: http://azimut.dc/rivercrs/api/v2/exist/14666?debug    :Germes # Чкалов
        #API_URL: http://azimut.dc/rivercrs/api/v2/exist/33679?debug    :Volga # Кутузов
        #API_URL: http://azimut.dc/rivercrs/api/v2/exist/29274?debug_qq :infoflot
        #API_URL: http://azimut.dc/rivercrs/api/v2/exist/30786?debug    :infoflot

        #API_URL: http://azimut.dc/rivercrs/api/v2/exist/28839?debug
        #API_URL: http://azimut.dc/rivercrs/api/v2/exist/33898?debug

        # http://azimut.dc/rivercrs/api/v2/exist/550402?debug?cached=1

        #Кулибин лишние палубы: http://azimut.dc/rivercrs/api/v2/exist/13966?debug

        $cache_key = "exist:$checkin_id";


        $cached = boolval(Input::get('cached'));

        # todo открыть
        if ($type == 'json') {
            # Вернуть кеш если он есть
            if (Cache::has($cache_key)) {
                if (isset($_GET['debug'])) {
                    $data = Cache::get($cache_key);
                    dd($data);
                } else {
                    $this->json(Cache::get($cache_key));
                    return;
                }
            }
        }

        if (!$checkin) {
            $this->checkin = self::$static_checkin = $checkin = Checkin::find($checkin_id);
        }

        if (!$checkin) {
            $this->error('no_checkin');
            return;
        }

        $exist_data = [
            'decks' => [],
        ];

        //$cached = true;

        if (isset($_GET['debug'])) {
            $cached = true;
        }

        if ($cached) {

            try {
                if ($checkin->eds_code) { // && $checkin->eds_code !== 'gama'
                    $class_name = mb_convert_case($checkin->eds_code, MB_CASE_TITLE, "UTF-8");
                    if (file_exists(base_path() . "/plugins/mcmraak/rivercrs/classes/exist/$class_name.php")) {
                        $exist_data = App::call("Mcmraak\Rivercrs\Classes\Exist\\$class_name@getExist", [
                            'checkin' => $checkin,
                            'realtime' => $realtime,
                        ]);
                    }
                }
            } catch (Exception $exception) {

            }
        }

        if (!$exist_data) {
            $exist_data = [
                'decks' => [],
            ];
        }


        $this->checkin = $checkin;
        $this->exist_data = $exist_data;
        $this->records = $this->exist_data['decks'];

        # Слияние данных из базы с данными из запроса
        $mix_data = $this->existMix();

        # Сортировка по палубам
        $mix_data = $this->reorderDescks($mix_data);

        $mix_data = [
            'decks' => $mix_data,
            'rooms' => $this->roomsHandler($mix_data),
            'eds' => $checkin->eds_code,
            'tariff_price1_title' => (isset($exist_data['tariff_price1_title'])) ? $exist_data['tariff_price1_title'] : ['name' => 'Руб.на 1 чел.', 'desc' => null],
            'tariff_price2' => (isset($exist_data['tariff_price2'])) ? $exist_data['tariff_price2'] : false,
            'tariff_price2_title' => (isset($exist_data['tariff_price2_title'])) ? $exist_data['tariff_price2_title'] : ['name' => 'Руб.на 1 чел.', 'desc' => null],
        ];

        $this->addQQ($mix_data);

        if (isset($_GET['debug_qq'])) {
            $dump = [];
            foreach ($mix_data['decks'] as $deck) {
                echo "<h1>Палуба: {$deck['name']}</h1>";
                foreach ($deck['cabins'] as $cabin) {
                    echo "<h4>Каюта: {$cabin['name']}</h4>";
                    echo "<div style='padding:5px'>{$this->json($cabin['QQ'])}</div>";
                    $dump[] = json_decode($cabin['QQ'], 1);
                }
            }
            dd($dump);
        } //dd(json_decode($mix_data['decks'][0]['cabins'][0]['QQ'], 1)); #DEBUG

        $this->patchPrices($mix_data);

        if (isset($_GET['debug'])) {
            dd($mix_data);
        }

        if ($type == 'json') {
            if ($cached) {
                $mix_data['cached'] = true;
                Cache::add($cache_key, $mix_data, 120); // Сохраняю на 2 часа
            }
            $this->json($mix_data);
            return;
        }

        if ($type === 'array') {
            $cabox->put($array_cache_key, $mix_data);
            //Cache::add($array_cache_key, $mix_data, 900); // Сохраняю на 15 ч
        }

        return $mix_data;
    }

    private function patchPrices(&$mix_data)
    {
        foreach ($mix_data['decks'] as &$deck) {
            $deck_id = intval($deck['id']);
            foreach ($deck['cabins'] as &$cabin) {
                $cabin_id = intval($cabin['id']);
                foreach ($cabin['prices'] as &$price) {
                    if (array_key_exists('main_places', $price)) {
                        if ($price['price_places'] !== $cabin['main_places']) {
                            continue;
                        }
                    }

                    $places_qnt = intval($price['price_places']);

                    $price['price_value'] = pricePatch()
                        ->getPrice($this->checkin->id, $deck_id, $cabin_id, $places_qnt, $price['price_value']);
                }
            }
        }
    }

    public function addQQ(&$mix_data)
    {
        if (!$this->cabins_cache) {
            $this->fillCabinsCache($mix_data);
        }

        foreach ($mix_data['decks'] as $deck_index => $deck) {
            foreach ($deck['cabins'] as $cabin_index => $cabin) {
                if (!@$this->cabins_cache[$cabin['id']]) {
                    $this->fillCabinsCache($mix_data);
                    if (!@$this->cabins_cache[$cabin['id']]) {
                        continue;
                    }
                }
                $mix_data['decks'][$deck_index]['cabins'][$cabin_index]['QQ'] =
                    $this->cabins_cache[$cabin['id']]['obj']->QQ($this->checkin, $deck, $cabin);
            }
        }
    }

    function fillCabinsCache($mix_data)
    {
        foreach ($mix_data['decks'] as $deck) {
            foreach ($deck['cabins'] as $cabin) {
                $this->cabins_cache[$cabin['id']] = [
                    'obj' => Cabin::find($cabin['id'])
                ];
            }
        }
    }

    # Необохимые поля:
    #     deck_name [eds-имя палубы] (или deck_data = [id, name] )
    #     cabin_name [eds-имя каюты] (или cabin_id - Идентификатор в локальной БД),
    #     price_places [основных мест у каюты в прайсе],
    #     price_value [цена]

    public $vals;

    public function addRecord($vals)
    {
        $this->vals = $vals;
        $deck_item = $this->getDeckKey(); // Определён ключ палубы
        $this->addCabin($this->records[$deck_item['key']]['cabins']);
        return [
            'deck_id' => $deck_item['id']
        ];
    }

    public function reorderDescks($mix_data)
    {

        $return = [];
        $decks = Deck::orderBy('sort_order')->get();
        foreach ($decks as $deck) {
            foreach ($mix_data as $item) {
                if ($item['id'] == $deck->id) {
                    $return[] = $item;
                }
            }
        }
        return $return;
    }

    # Функция строит первый уровень (decks) массива records
    # Возвращает индекс палубы в этом уровне
    # На вход принимает либо eds_name палубы либо её местный id (int)

    public function getDeckKey()
    {
        $deck_name = (isset($this->vals['deck_name'])) ? $this->vals['deck_name'] : null;
        $deck_data = (isset($this->vals['deck_data'])) ? $this->vals['deck_data'] : null;

        if ($deck_data) {
            $deck_id = $deck_data['id'];
            $deck_name = $deck_data['name'];

            $key = 0;
            foreach ($this->records as $deck) {
                if ($deck['id'] == $deck_id) {
                    return [
                        'key' => $key,
                        'id' => $deck_id
                    ];
                }
                $key++;
            }

            $this->records[] = [
                'id' => $deck_id,
                'name' => $deck_name,
                'cabins' => [],
            ];

            return [
                'key' => count($this->records) - 1,
                'id' => $deck_id
            ];
        }

        $deck_id = false;
        foreach ($this->deck_cache as $key => $item) {
            if ($deck_name == $item) {
                $deck_id = $key;
            }
        }

        if (!$deck_id) {
            $deck = app('Mcmraak\Rivercrs\Classes\Getter')->getDeck($deck_name);

            $this->deck_cache[$deck->id] = $deck_name;
            $this->records[] = [
                'id' => $deck->id,
                'name' => $deck->name,
                'cabins' => []
            ];
            return [
                'key' => count($this->records) - 1,
                'id' => $deck->id
            ];
        }

        $key = 0;
        foreach ($this->records as $record) {
            if ($record['id'] == $deck_id) {
                return [
                    'key' => $key,
                    'id' => $deck_id
                ];
            }
            $key++;
        }
    }

    # Добавить каюту (для addRecord)
    public function addCabin(&$cabins)
    {

        $cabin_name = (isset($this->vals['cabin_name'])) ? $this->vals['cabin_name'] : null;
        $cabin_id = (isset($this->vals['cabin_id'])) ? $this->vals['cabin_id'] : null;

        $cabin = $this->getCabinCache($cabin_name, $cabin_id);

        if (!$cabin) {
            return;
        }

        $add = true;
        $update_key = 0;
        foreach ($cabins as $item) {
            if (
                $item['id'] == $cabin->id &&
                $item['name'] == $cabin->category &&
                $item['main_places'] == $cabin->places_main_count &&
                $item['extra_places'] == $cabin->places_extra_count
            ) {
                $add = false;
                break;
            }
            $update_key++;
        }

        if ($add) {
            $cabins[] = [
                'id' => $cabin->id,
                'name' => $cabin->category,
                'main_places' => $cabin->places_main_count,
                'extra_places' => $cabin->places_extra_count,
                'prices' => [],
            ];
            $update_key = count($cabins) - 1;
        }

        // $update_key - Определён ключ каюты

        if (!$this->exist_data) {
            $this->addPrice($cabins[$update_key]['prices']);
            $cabins[$update_key]['eds'] = $this->vals['eds'];
        } else {
            if (!count($cabins[$update_key]['prices'])) {
                $this->addPrice($cabins[$update_key]['prices']);
            }
        }

        //$cabins[$update_key] = $this->addQQ($cabins[$update_key], $cabin);

    }

    /* TODO:DEPRICATED
    function addQQ($cabinRenderData, $cabin)
    {
        $cabinRenderData['QQ'] = $cabin->QQ($this->checkin, $cabinRenderData);
        return $cabinRenderData;
    }
    */

    public function roomsHandler($mix_data)
    {
        if (!$mix_data) {
            return [];
        }

        # Номер кают забитые вручную
        $ev_rooms = $this->checkin->motorship->exist_rooms;
        $ev_rooms = json_decode($ev_rooms, 1);
        $ex_rooms = $this->exist_data['rooms'] ?? [];

        if (!$ev_rooms) {
            foreach ($mix_data as $deck) {
                foreach ($deck['cabins'] as $cabin) {
                    $return[] = [
                        'n' => 'Под запрос',
                        'c' => $cabin['id'],
                        'd' => $deck['id'],
                        'f' => 1
                    ];
                }
            }
            $this->roomsChecker(null, $ex_rooms);
            return $return;
        }

        # Записывает в базу какие каюты он не нашёл (не добавили на схему)
        $this->roomsChecker($ev_rooms, $ex_rooms);

        $return = [];

        foreach ($ev_rooms as $ev_room) {
            $free = 0;
            $deck_id = 0;
            foreach ($ex_rooms as $ex_room) {
                if ($ex_room['n'] == $ev_room['n']) {
                    $free = 1;
                    $deck_id = $ex_room['d'];
                    continue;
                }
            }

            $ev_room['c'] = intval($ev_room['c']);
            $ev_room['f'] = $free;
            $ev_room['d'] = ($free) ? $deck_id : 0;

            $return[] = $ev_room;

        }

        $return = new Collection($return);
        $return = $return->sortBy('n')->toArray();
        $return = array_values($return);

        foreach ($mix_data as $deck) {
            foreach ($deck['cabins'] as $cabin) {
                $empty = true;
                foreach ($return as $room) {
                    if ($cabin['id'] == $room['c'] && $room['f'] && $room['d'] == $deck['id']) {
                        $empty = false;
                        break;
                    }
                }
                if ($empty) {
                    $return[] = [
                        'n' => 'Под запрос',
                        'c' => $cabin['id'],
                        'd' => $deck['id'],
                        'f' => 1
                    ];
                }
            }
        }

        return $return;
    }

    public function roomsChecker($ev_rooms, $ex_rooms)
    {
        if (!$ev_rooms) {
            $ev_rooms = [];
        }

        $not_exist = [];
        foreach ($ex_rooms as $ex_room) {
            $find = false;
            foreach ($ev_rooms as $ev_room) {
                if ($ev_room['n'] == $ex_room['n']) {
                    $find = true;
                    #echo "{$ev_room['n']} == {$ex_room['n']} <br>";
                    break;
                }
            }
            if (!$find) {
                $not_exist[] = $ex_room['n'];
            }
        }

        $text = ($not_exist) ? join(',', $not_exist) : '';
        DB::table('mcmraak_rivercrs_motorships')
            ->where('id', $this->checkin->motorship_id)
            ->update([
                'not_exist_rooms' => $text
            ]);
    }

    public function getCabin()
    {

        $data = Input::all();

        $checkin_id = intval($data['checkin_id']);
        $category_id = intval($data['c']);
        $room_number = $data['n'];
        $free_status = $data['f'];
        $deck_id = $data['d'];
        $check = $data['check'];

        $room_data = [
            'room_number' => $room_number,
            'free_status' => $free_status,
            'cabin_data' => $this->getCabinData($checkin_id, $room_number, $deck_id),
            'free_status' => $free_status,
            'check' => $check,
            'deck_id' => $deck_id,
        ];

        //Log::debug($room_data);

        /*
         array (
              'room_number' => '62',
              'free_status' => '1',
              'cabin_data' =>
              array (
                'id' => 169,
                'name' => 'Полулюкс Б (3х мест)',
                'main_places' => 3,
                'extra_places' => 0,
                'prices' =>
                array (
                  0 =>
                  array (
                    'price_places' => 1,
                    'price_value' => 49600,
                  ),
                  1 =>
                  array (
                    'price_places' => 2,
                    'price_value' => 39000,
                  ),
                ),
                'eds' => true,
              ),
              'check' => 'false',
              'deck_id' => '5',
            )
        */

        $place_names = [
            1 => 'Одно',
            2 => 'Двух',
            3 => 'Трёх',
            4 => 'Четырёх',
            5 => 'Пяти',
            6 => 'Шести',
            7 => 'Семи',
            8 => 'Восьми',
            9 => 'Девяти',
            10 => 'Десяти',
        ];

        $cabin = \Mcmraak\Rivercrs\Models\Cabins::find($category_id);

        $html = (string)\View::make(
            'mcmraak.rivercrs::cabin_exist_modal',
            [
                'cabin' => $cabin,
                'placenames' => $place_names,
                'room_data' => $room_data
            ]
        );
        $this->json([
            'html' => $html
        ]);
    }

    # http://azimut.dc/rivercrs/debug/Exist@getCabinData
    public function getCabinData($checkin_id, $room_number, $deck_id)
    {
        $data = $this->get($checkin_id, 'array', false);

        $category_id = 0;
        foreach ($data['rooms'] as $room) {
            if ($room['n'] == $room_number) {
                $category_id = $room['c'];
                break;
            }
        }

        foreach ($data['decks'] as $deck) {
            if ($deck['id'] != $deck_id) {
                continue;
            }
            foreach ($deck['cabins'] as $cabin) {
                if ($cabin['id'] == $category_id) {
                    return $cabin;
                }
            }
        }

        return false;
    }

    public function getCabinCache($eds_cabin_name, $cabin_id = null)
    {
        if (!$this->checkin) {
            $this->checkin = self::$static_checkin;
        }

        if ($cabin_id) {
            foreach ($this->cabins_cache as $id => $item) {
                if ($id == $cabin_id) {
                    return $this->cabins_cache[$cabin_id]['obj'];
                }
            }
            $cabin_object = Cabin::find($cabin_id);
            $this->cabins_cache[$cabin_id] = [
                'eds_cabin_name' => $cabin_object->{$this->checkin->eds_code . '_name'},
                'obj' => $cabin_object,
            ];
            return $this->cabins_cache[$cabin_object->id]['obj'];
        }

        foreach ($this->cabins_cache as $item) {
            if ($item['eds_cabin_name'] == $eds_cabin_name) {
                return $item['obj'];
            }
        }

        $cabin_object = Cabin::where($this->checkin->eds_code . '_name', $eds_cabin_name)
            ->where('motorship_id', $this->checkin->motorship_id)
            ->first();


        if (!$cabin_object) {
            # Каюта не найдена, она могла быть добавлена в исключения
            return false;
        }

        $this->cabins_cache[$cabin_object->id] = [
            'eds_cabin_name' => $eds_cabin_name,
            'obj' => $cabin_object,
        ];

        return $this->cabins_cache[$cabin_object->id]['obj'];
    }

    # Добавить цену и места (для addRecord)
    public function addPrice(&$prices)
    {
        $price_places = intval($this->vals['price_places']);
        $price_value = intval($this->vals['price_value']);

        $add = true;
        foreach ($prices as $price) {
            if (
                $price['price_places'] == $price_places &&
                $price['price_value'] == $price_value
            ) {
                $add = false;
                break;
            }
        }
        if ($add) {
            $price2_value = (isset($this->vals['price2_value'])) ? intval($this->vals['price2_value']) : '';

            $prices[] = [
                'price_places' => $price_places,
                'price_value' => $price_value,
                'price2_value' => $price2_value
            ];
        }
    }

    public $existMix = [];

    public function existMix()
    {
        $mix = DB::table('mcmraak_rivercrs_pricing as pricing')
            ->where('pricing.checkin_id', $this->checkin->id)
            ->join(
                'mcmraak_rivercrs_checkins as checkin',
                'checkin.id',
                '=',
                'pricing.checkin_id'
            )
            ->join(
                'mcmraak_rivercrs_cabins as cabin',
                'cabin.id',
                '=',
                'pricing.cabin_id'
            )
            ->join(
                'mcmraak_rivercrs_decks_pivot as decks_pivot',
                'decks_pivot.cabin_id',
                '=',
                'cabin.id'
            )
            ->join(
                'mcmraak_rivercrs_decks as deck',
                'deck.id',
                '=',
                'decks_pivot.deck_id'
            )
            ->select(
                'deck.id as deck_id',
                'deck.name as deck_name',
                'cabin.id as cabin_id',
                'cabin.category as cabin_name',
                'cabin.places_main_count as cabin_main_places',
                'cabin.places_extra_count as cabin_extra_places',
                'pricing.price_a as price_value',
                'pricing.price_b as price2_value',
            )
            ->get();

        foreach ($mix as $record) {
            $record = (object) $record;

            if (!$record->price_value) {
                continue;
            }

            $this->addRecord([
                'deck_data' => [
                    'id' => $record->deck_id,
                    'name' => $record->deck_name,
                ],
                'cabin_id' => $record->cabin_id,
                'price_places' => $record->cabin_main_places,
                'price_value' => $record->price_value,
                'price2_value' => $record->price2_value
            ]);
        }

        return $this->records;
    }
}
