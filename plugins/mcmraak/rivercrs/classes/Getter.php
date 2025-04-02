<?php namespace Mcmraak\Rivercrs\Classes;

use Mcmraak\Rivercrs\Classes\Parser;
use Exception;
use Log;
use Mcmraak\Rivercrs\Models\Motorships as Ship;
use Mcmraak\Rivercrs\Models\Cabins as Cabin;
use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use Mcmraak\Rivercrs\Models\Waybills as Waybill;
use Mcmraak\Rivercrs\Models\Pricing;
use Mcmraak\Rivercrs\Models\Towns as Town;
use Mcmraak\Rivercrs\Models\Decks as Deck;
use Mcmraak\Rivercrs\Traits\Gama;
use Mcmraak\Rivercrs\Traits\Germes;
use Mcmraak\Rivercrs\Traits\Infoflot;
use Mcmraak\Rivercrs\Traits\InfoflotV2;
use Mcmraak\Rivercrs\Traits\InfoflotV3;
use Mcmraak\Rivercrs\Traits\Volga;
use Mcmraak\Rivercrs\Traits\Waterway;
use System\Models\File;
use DB;
use Input;
use Carbon\Carbon;
use Mcmraak\Rivercrs\Classes\Idmemory as ID;
use Mcmraak\Rivercrs\Models\Log as JLog;
use Mcmraak\Rivercrs\Classes\CacheSettings;
use Cache;
use Config;
use App;
use Mcmraak\Rivercrs\Classes\Search;

class Getter extends Parser
{
    use Waterway;
    use Gama;
    use Germes;
    use Volga;

    //use Infoflot;
    //use InfoflotV2;
    use InfoflotV3;

    public $testMode = true;

    public function json($array)
    {
        echo json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function checkSeparator($string = null)
    {
        $dash_towns = CacheSettings::get('dash_towns');


        foreach ($dash_towns as $dash_town) {
            $words = explode('-', $dash_town['name']);
            $regexp = join('[-– +]+', $words);
            preg_match_all("/$regexp/ui", $string, $matches);
            foreach ($matches[0] as $match) {
                $new_word = preg_replace('/[-– +]+/u', '⏹', $match);
                $string = str_replace($match, $new_word, $string);
            }
        }

        # Экранируем содержимое в скобках
        $string = $this->hooksReplace($string);
        $string = str_replace(['+', '–'], '-', $string);
        return $string;
    }

    public static function removeEds($eds_code)
    {
        $checkins = Checkin::where('eds_code', $eds_code)->get();
        $checkins_count = $checkins->count();

        $waybills_before = Waybill::count();
        $prices_before = Pricing::count();
        foreach ($checkins as $checkin) {
            $checkin->delete();
        }
        $waybills_after = Waybill::count();
        $prices_after = Pricing::count();
        $waybills_count = $waybills_before - $waybills_after;
        $prices_count = $prices_before - $prices_after;

        $cabins = Cabin::whereNotNull($eds_code . '_name')->get();
        $cabins_count = $cabins->count();
        foreach ($cabins as $cabin) {
            $cabin->delete();
        }

        return [
            'checkins' => $checkins_count,
            'waybills' => $waybills_count,
            'cabins' => $cabins_count,
            'prices' => $prices_count,
        ];
    }

    public function hooksReplace($string)
    {
        // Заменяем символы внутри круглых скобок
        while (preg_match('/\([^()]+\)/', $string, $matches)) {
            foreach ($matches as $item) {
                $new_item = str_replace(['+', '–', '-'], '⏹', $item);
                $new_item = str_replace('(', '⏴', $new_item);
                $new_item = str_replace(')', '⏵', $new_item);
                $string = str_replace($item, $new_item, $string);
            }
        }
        return $string;
    }

    public function getTownId($name, $eds_code = null)
    {

        $name = trim($name);
        $name = preg_replace('/ {2,}/u', ' ', $name);
        #$charset = mb_detect_encoding($name);
        #$name = iconv($charset, "UTF-8", $name);
        $name = str_replace('⏹', '-', $name);
        $name = str_replace('⏴', '(', $name);
        $name = str_replace('⏵', ')', $name);


        $town = Town::where('name', $name)->first();

        if ($town) {
            #if($town->id > 201) { # Закомментировать
            #    $town->eds_code = $eds_code; # Закомментировать
            #    $town->save(); # Закомментировать
            #}
            return $town->id;
        }
        $town = new Town;
        $town->name = $name;
        $town->eds_code = $eds_code;
        $town->save();
        return $town->id;
    }

    public function infoPanel()
    {
        $this->json([
            'active_checkins_count' => Checkin::where('active', 1)->count(),
            'inactive_checkins_count' => Checkin::where('active', 0)->count(),
            'noeds_checkins_count' => Checkin::where('eds_id', 0)->count(),
            'motorships_count' => Ship::count(),
            'cabins_count' => Cabin::count(),
            'towns_count' => Town::count(),
            'jlog_count' => JLog::count(),
            'waterway_time' => ID::lastTime('waterway'),
            'volga_time' => ID::lastTime('volga'),
            'gama_time' => ID::lastTime('gama'),
            'germes_time' => ID::lastTime('germes'),
            'infoflot_time' => ID::lastTime('infoflot'),
            'ids_count' => ID::idsCount(),
        ]);
    }

    public function townsCheck()
    {
        $towns = Town::get();

        foreach ($towns as $town) {
            $test = Town::where('name', $town->name)->where('id', '<>', $town->id)->get();
            if ($test->count()) {

                $list = [];
                $list[] = $town->name;
                foreach ($test as $bad_town) {
                    $list[] = $bad_town->name;
                }
                $list = join('->', $list);
                #Log::debug($list);

                foreach ($test as $bad_town) {
                    $bad_town->merger = $town->id;
                    $bad_town->save();
                }
            }
        }

        $this->json([
            'message' => 'Города обработаны'
        ]);
    }

    public function trashCleaner()
    {
        $id = Input::get('id');
        $t = 'mcmraak_rivercrs_pricing';
        $per_line = 50;
        #$id = 'init';# DEBUG

        if ($id == 'init') {
            $ids = [];
            $ids[] = 'steep1';

            $checkins = Checkin::get();
            $tring_ids = [];
            $count = $per_line;
            $max = $checkins->count();
            $i = 0;
            foreach ($checkins as $checkin) {
                if ($count > 0 && $max - $i > $per_line) {
                    $tring_ids[] = $checkin->id;
                    $count--;
                } else {
                    if ($tring_ids)
                        $ids[] = 'steep2:' . join(',', $tring_ids);
                    $tring_ids = [];
                    $count = $per_line;
                }
                $i++;
            }

            #dd($ids); # DEBUG
            $this->json($ids);
            return;
        }


        # Проверка 1: Удаление цен для несуществующих заездов
        if ($id == 'steep1') {
            $zero_count = DB::table($t)->where('checkin_id', 0)->count();
            if (!$zero_count) {
                $message = "Удалено записей: $zero_count";
                DB::table($t)->where('checkin_id', 0)->delete();
                JLog::add('clean', 'Getter@trashCleaner', 'Очистка мусора', $message);
            }
            $this->json([
                'message' => 'Сборщик мусора: Проверка пустых заездов'
            ]);
            return;
        }

        if (strpos($id, 'steep2') === 0) {

            $id = explode(':', $id);
            $ids = explode(',', $id[1]);

            #Log::debug($id[1]);

            $prices = DB::table($t)->whereIn('checkin_id', $ids)->get();

            $scan_checkin_id = 0;
            foreach ($prices as $price) {
                # Проверка 2: Перебираются цены, и удалаяются те для которых нет кают
                $cabin = Cabin::find($price->cabin_id);
                if (!$cabin) {
                    DB::table($t)->where('checkin_id', $price->checkin_id)
                        ->where('cabin_id', $price->cabin_id)
                        ->delete();
                }
                # Проверка 3: Перебираются цены, и удалаяются те для которых нет заездов
                if ($scan_checkin_id != $price->checkin_id) {
                    $scan_checkin_id = $price->checkin_id;
                    $checkin = Checkin::find($price->checkin_id);
                    if (!$checkin) {
                        DB::table($t)->where('checkin_id', $price->checkin_id)
                            ->where('cabin_id', $price->cabin_id)
                            ->delete();
                        # Проверка 4: Удаляются маршруты для которых нет заездов
                        Waybill::where('checkin_id', $price->checkin_id)->delete();
                    }
                }
            }

            # Деактивация заездов с нулевыми ценами или если цен нет
            foreach ($ids as $checkin_id) {
                $sum = DB::table($t)
                    ->where('checkin_id', $checkin_id)
                    ->sum('price_a');
                if (!$sum) {
                    DB::table('mcmraak_rivercrs_checkins')
                        ->where('id', $checkin_id)
                        ->where('eds_code', '<>', 'gama')
                        ->update(['active' => 0]);
                    $checkin = Checkin::find($checkin_id);
                    if ($checkin->eds_code != 'gama') {
                        $message = "Деактивирован заезд $checkin_id (нет цен) eds: {$checkin->eds_code}";
                        JLog::add('test', 'Getter@trashCleaner', $message, $message);
                    }
                }
            }

            # Удаление заездов для которых нет теплоходов
            $checkins = Checkin::whereIn('id', $ids)->get();
            foreach ($checkins as $checkin) {
                $ship = Ship::find($checkin->motorship_id);
                if (!$ship) {
                    $checkin->delete();
                }
            }

            $this->json([
                'message' => "Проверка $per_line заездов id:{$ids[0]}..." . $ids[count($ids) - 1]
            ]);
            return;
        }

    }

    public function dublesCleaner()
    {
        $id = Input::get('id');
        $per_line = 30;

        #$id = 'init';

        if ($id == 'init') {
            $ids = $this->idsPackager(Checkin::get(), $per_line);
            $this->json($ids);
            return;
        }


        $ids = explode(',', $id);

        foreach ($ids as $checkin_id) {
            $this->checkDubleCheckins($checkin_id);
        }


        $this->json([
            'message' => "Проверка $per_line заездов id:{$ids[0]}..." . $ids[count($ids) - 1]
        ]);
    }

    public function notActualCleaner()
    {
        $id = Input::get('id');
        $per_line = 30;

        #$id = 'init';

        if ($id == 'init') {
            $ids = $this->idsPackager(Checkin::get(), $per_line);
            $this->json($ids);
            return;
        }


        $ids = explode(',', $id);

        foreach ($ids as $checkin_id) {
            $this->notActualCheck($checkin_id);
        }


        $this->json([
            'message' => "Проверка $per_line заездов id:{$ids[0]}..." . $ids[count($ids) - 1]
        ]);
    }

    public function createCheckinsBlocks()
    {
        $id = Input::get('id');
        $per_line = 10;

        #$id = 'init';

        if ($id == 'init') {
            $ids = $this->idsPackager(Checkin::get(), $per_line);
            $this->json($ids);
            return;
        }


        $ids = explode(',', $id);

        app('\Mcmraak\Rivercrs\Classes\Search')->renderCheckinsBlocks($ids, ['update' => true]);


        $this->json([
            'message' => "Подготовлен кеш $per_line заездов id:{$ids[0]}..." . $ids[count($ids) - 1]
        ]);
    }

    public function notActualCheck($checkin_id)
    {
        $checkin = Checkin::find($checkin_id);
        if (!$checkin->eds_code) return;
        if (ID::isExist($checkin->eds_code, $checkin->eds_id)) return;

        #TODO: Временная мера
        //        if($checkin->eds_code != 'waterway') {
        //            $checkin->delete();
        //        }
    }

    public function checkCabins()
    {

        $debug = false; # http://azimut.dc/rivercrs/debug/Getter@checkCabins

        if (isset($_GET['debug'])) $debug = $_GET['debug'];

        $id = Input::get('id');
        $per_line = 30;

        if ($debug) $id = $debug;

        if ($id == 'init') {
            $ids = $this->idsPackager(Cabin::get(), $per_line);
            if ($debug) dd($ids);
            $this->json($ids);
            return;
        }


        $ids = explode(',', $id);

        foreach ($ids as $cabin_id) {
            $cabin = Cabin::find($cabin_id);

            $test = $this->isCabinNotLet($cabin->category, $cabin->motorship_id);

            #$answer = ($test)?'да':'нет';
            #Log::debug('Каюта: "'.$cabin->category.'" для теплохода: "'.$cabin->motorship->name.'", задержать? '.$answer);

            if ($test) {
                $cabin->delete();
                $message = "Удалена каюта {$cabin->category} для теплохода {$cabin->motorship->name}";
                JLog::add('test', 'Getter@checkCabins', $message, $message);
            }
        }

        $this->json([
            'message' => "Проверка $per_line кают id:{$ids[0]}..." . $ids[count($ids) - 1]
        ]);
    }

    # http://azimut.dc/rivercrs/debug/Getter@excludedMotorshipsCheckins?id=init&debug=1
    # http://azimut.dc/rivercrs/debug/Getter@excludedMotorshipsCheckins?id=infoflot:11&debug=1
    function excludedMotorshipsCheckins()
    {
        $debug = false;
        if (isset($_GET['debug'])) $debug = $_GET['debug'];
        $id = Input::get('id');

        if ($id == 'init') {
            $ids = CacheSettings::getBadShips();
            if ($debug) dd($ids);
            $this->json($ids);
            return;
        }

        $data = explode(':', $id);
        $ship_id = $data[0];
        $eds_name = $data[1];

        $ship = Ship::find($ship_id);
        if (!$ship) {
            $this->json([
                'message' => 'Теплоход отсутствует в базе'
            ]);
            return;
        }

        if (!$ship->{$eds_name . '_id'}) {
            $this->json([
                'message' => 'Данный теплоход не содержит метки eds'
            ]);
            return;
        }

        $checkins_ids = Checkin::where('motorship_id', $ship->id)
            ->where('eds_code', $eds_name)
            ->pluck('id')->toArray();

        if ($checkins_ids) {
            Checkin::whereIn('id', $checkins_ids)->delete();
            Waybill::whereIn('checkin_id', $checkins_ids)->delete();
            Pricing::whereIn('checkin_id', $checkins_ids)->delete();
            (new Search)->delCache($checkins_ids);
        }

        DB::table('mcmraak_rivercrs_cabins')
            ->where('motorship_id', $ship->id)
            ->whereNotNull($eds_name . '_name')
            ->delete();

        $this->json([
            'message' => 'Заезды в количестве ' . count($checkins_ids) . ' для данного теплохода с eds-меткой ' . $eds_name . ' удалены'
        ]);
    }

    public function idsPackager($records, $per_line)
    {
        $ids = [];
        $i = 0;
        $line = [];
        foreach ($records as $record) {
            if ($i < $per_line) {
                $line[] = $record->id;
            } else {
                $ids[] = join(',', $line);
                $line = [];
                $i = 0;
            }
            $i++;
        }
        if ($line) $ids[] = join(',', $line);
        return $ids;
    }

    public function isCabinNotLetTest()
    {

        # http://azimut.dc/rivercrs/debug/Getter@isCabinNotLetTest

        $not_let = [['name' => 'Эко (2+1) {!11}']]; // Разрешить 'Эко (2+1)' для всех теплоходов кроме 11 (true = запретить)
        $test = $this->isCabinNotLet('Эко (2+1)', 11, $not_let);
        if ($test !== true) die('test-1 error');

        $not_let = [['name' => 'Эко (2+1) {11}']]; // Разрешить 'Эко (2+1)' только для теплоходов 11 (false = разрешить)
        $test = $this->isCabinNotLet('Эко (2+1)', 11, $not_let);
        if ($test !== false) die('test-2 error');

        $not_let = [['name' => 'Несуществует {11}']]; // Разрешить 'Несуществует' только для теплоходов 11 (false = разрешить)
        $test = $this->isCabinNotLet('Эко (2+1)', 11, $not_let);
        if ($test !== false) die('test-3 error');

        $not_let = [['name' => 'Несуществует {!11}']]; // Разрешить 'Несуществует' для всех теплоходов кроме 11 (false = разрешить)
        $test = $this->isCabinNotLet('Эко (2+1)', 11, $not_let);
        if ($test !== false) die('test-4 error');

        $not_let = [['name' => 'Эко (2+1) {!11}']]; // Разрешить 'Эко (2+1)' для всех теплоходов кроме 11 (false = разрешить)
        $test = $this->isCabinNotLet('Эко (2+1)', 15, $not_let);
        if ($test !== false) die('test-5 error');

        $not_let = [['name' => 'Эко (2+1) {11}']]; // Разрешить 'Эко (2+1)' только для теплоходов 11 (true = запретить)
        $test = $this->isCabinNotLet('Эко (2+1)', 15, $not_let);
        if ($test !== true) die('test-6 error');

        $not_let = [['name' => 'Несуществует {11}']]; // Разрешить 'Несуществует' только для теплоходов 11 (false = разрешить)
        $test = $this->isCabinNotLet('Эко (2+1)', 15, $not_let);
        if ($test !== false) die('test-7 error');

        $not_let = [['name' => 'Несуществует {!11}']]; // Разрешить 'Несуществует' для всех теплоходов кроме 11 (false = разрешить)
        $test = $this->isCabinNotLet('Эко (2+1)', 15, $not_let);
        if ($test !== false) die('test-8 error');

        $test = $this->isCabinNotLet('Эко (2+1)', 11);
        if ($test !== true) die('test-9 error');

        echo 'all tests ok';
    }

    public function isCabinNotLet($cabin_name, $motorship_id, $not_let = null)
    {

        $DENY = false; # ( По умолчанию разрешить )

        $cabin_name = trim($cabin_name);
        $not_let = ($not_let) ? $not_let : CacheSettings::get('not_let_cabins');

        if (!$not_let) return false;
        foreach ($not_let as $item) {
            if ($item['name'] == $cabin_name) {
                return true; # Точное вхождение, сразу запрет
            }

            $options = preg_match('/\{([^{}]+)\}/', $item['name'], $matches);
            if (isset($matches[1])) {
                $ships = explode(',', $matches[1]);
                $item['name'] = trim(str_replace($matches[0], '', $item['name']));

                # $cabin_entry это совпадение по каюте
                $C = ($item['name'] == $cabin_name);
                if (!$C) continue; // Если убрать это условие, то правило типа NAME {N} будет обязывать N иметь только одну NAME

                # $allow это направление исключения кораблей в зависимости от ! !=false
                $A = (preg_match('/^!/', $ships[0])) ? false : true;
                if (!$A) $ships[0] = str_replace('!', '', $ships[0]);

                # $ship_exist это совпадение по кораблю
                $S = in_array($motorship_id, $ships);

                # Матрица условий
                if ($C + $S + $A == 2) $DENY = true;

            }
        }

        return $DENY;
    }

    public function getMotorship($name, $eds_field, $eds_id, $desc = '')
    {
        if (CacheSettings::shipIsBad($name, $eds_field)) {
            $this->json([
                'message' => 'Данный теплоход исключён'
            ]);
            die;
        }

        if (strpos($eds_field, '_id') === false) {
            $eds_field .= '_id';
        }

        $ship = Ship::where($eds_field, $eds_id)->first();
        if ($ship) return $ship;

        $ship = Ship::where('name', 'like', "%$name%")->first();

        if ($ship) {
            $ship->{$eds_field} = $eds_id;
            $ship->save();
            return $ship;
        }

        $ship = new Ship;
        $ship->name = $name;
        $ship->desc = $desc;
        $ship->add_a = '';
        $ship->add_b = '';
        $ship->booking_discounts = '';
        $ship->social_discounts = '';
        $ship->youtube = '';
        $ship->banner = '';
        $ship->techs = [];
        $ship->{$eds_field} = $eds_id;
        $ship->save();
        return $ship;
    }

    public function getDeck($deck_name)
    {
        $deck_name = trim($deck_name);
        $deck = Deck::where('name', 'like', "%$deck_name%")->first();
        if (!$deck) {
            $deck = new Deck;
            $deck->name = $deck_name;
            $deck->sort_order = 0;
            $deck->save();
        } else {
            if ($deck->parent_id) {
                $ParentDeck = Deck::find($deck->parent_id);
                if ($ParentDeck) {
                    $deck = $ParentDeck;
                }
            }
        }

        return $deck;
    }

    public function deckPivotCheck($cabin_id, $deck_id)
    {
        $pivot_test = DB::table('mcmraak_rivercrs_decks_pivot')
            ->where('cabin_id', $cabin_id)
            ->where('deck_id', $deck_id)->count();
        if (!$pivot_test) {
            DB::table('mcmraak_rivercrs_decks_pivot')->insert([
                'cabin_id' => $cabin_id,
                'deck_id' => $deck_id
            ]);
        }
    }

    public function diffInDays($date_a, $date_b)
    {
        $start = Carbon::parse($date_a);
        $end = Carbon::parse($date_b);
        return $end->diffInDays($start);
    }

    public function idsTime()
    {
        return intval(CacheSettings::get('ids_memory')) * 60;
    }

    public function waterwayId($id)
    {
        return ID::test('waterway', $id, $this->idsTime());
    }

    public function volgaId($id)
    {
        return ID::test('volga', $id, $this->idsTime());
    }

    public function gamaId($id)
    {
        return ID::test('gama', $id, $this->idsTime());
    }

    public function germesId($id)
    {
        return ID::test('germes', $id, $this->idsTime());
    }

    public function infoflotId($id)
    {
        return ID::test('infoflot', $id, $this->idsTime());
    }

    public function daysDiffCheck($diff, $id)
    {
        if ($diff < CacheSettings::get('days_diff')) {
            $message = "Заезд:$id Не соотвествует условию (Время заезда в днях = $diff)";
            JLog::add('check', 'Getter@daysDiffCheck', $message, $message);
            $this->json([
                'message' => $message
            ]);
            die;
        }
    }

    public function testCheckin($id, $eds_code)
    {
        # Тест 1: Записался ли заезд в базу данных?
        # Тест 2: Установлен ли на заезде код внешнего источника данных?
        if (!$this->testMode) return;
        $checkin = Checkin::where('eds_code', $eds_code)->first();
        if (!$checkin) {
            $this->testCheckinLog($id, 'Error: NO checkin', $eds_code);
            return;
        }

        # Тест 3: Связан ли заезд с теплоходом
        # Тест 4: Существует ли теплоход соотвествующий данному заезду
        $ship = $checkin->motorship;
        if (!$ship) {
            $this->testCheckinLog($id, 'Error:  NO ship', $eds_code);
            return;
        }

        # Тест 5: Создан ли маршрут для данного заезда
        $waybill = $checkin->waybill_id;
        $waybill_count = count($waybill);
        if (!$waybill_count) {
            $this->testCheckinLog($id, 'Error: NO waybill', $eds_code);
            return;
        }

        # (Не проверяет "gama")
        if ($eds_code !== 'gama') {
            # Тест 6: Существуют ли записи о ценах
            $prices = DB::table('mcmraak_rivercrs_pricing')
                ->where('checkin_id', $checkin->id)->count();
            if (!$prices) {
                $this->testCheckinLog($id, 'Error: NO prices', $eds_code);
                return;
            }

            # Тест 7: Не являются ли цены нулями
            $prices_sum = DB::table('mcmraak_rivercrs_pricing')
                ->where('checkin_id', $checkin->id)->sum('price_a');

            if (!$prices_sum) {
                $this->testCheckinLog($id, 'NULL prices', $eds_code);
                return;
            }
        }

        $this->checkDubleCheckins($checkin);

        #$this->testCheckinLog($id, 'OK');
    }

    public function testCheckinLog($id, $message, $eds_code)
    {
        $message = "$message [eds_code:$eds_code, eds_id:$id]";
        JLog::add('test', 'Getter@testCheckin', $message, $message);
    }

    public function findAttr($array, $attr_name)
    {
        $deck_id = $array['@attributes'][$attr_name];
        if (isset($array['@attributes'][$attr_name])) {
            return $array['@attributes'][$attr_name];
        }
        if (isset($array[$attr_name])) {
            return $array[$attr_name];
        }
        return false;
    }

    # Удалить продублированные заезды
    public function checkDubleCheckins($checkin)
    {

        if (!is_object($checkin)) {
            $checkin = Checkin::find($checkin);
        }

        if (!$checkin) return; // Заезд может быть уже удалён


        // EDS Проверка

        $eds_code = $checkin->eds_code;
        $eds_id = $checkin->eds_id;

        if ($eds_code && $eds_id) {
            $eds_checkins = Checkin::where('eds_code', $eds_code)->
            where('eds_id', $eds_id)->
            orderBy('id')->
            get();
            if ($eds_checkins->count() > 1) {
                $count = $eds_checkins->count();
                for ($i = 0; $i < $count - 1; $i++) {
                    $eds_checkins[$i]->delete();
                }
            }
        }

        $t_motorship_id = $checkin->motorship_id;
        $t_date = substr($checkin->date, 0, -9);
        $t_dateb = substr($checkin->dateb, 0, -9);
        $t_waybill_key = $checkin->waybillKey();

        $test = Checkin::where('date', 'like', "%$t_date%")
            ->where('dateb', 'like', "%$t_dateb%")
            ->where('motorship_id', $t_motorship_id)
            ->get();
        if (!$test->count()) return;

        foreach ($test as $t) {
            if ($t->id != $checkin->id && $t_waybill_key == $t->waybillKey()) {
                if ($t->id > $checkin->id) {
                    $checkin->delete();
                } else {
                    $t->delete();
                }
            }
        }
    }

    public function cleanPrices()
    {
        $prices = DB::table('mcmraak_rivercrs_pricing')->get();
        $deleted = 0;
        foreach ($prices as $price) {
            $checkin = Checkin::find($price->checkin_id);
            if (!$checkin) {
                DB::table('mcmraak_rivercrs_pricing')
                    ->where('checkin_id', $price->checkin_id)
                    ->delete();
                $deleted++;
            }
        }

        $this->json([
            'message' => 'Цены обработаны, заездов для которых очищены цены: ' . $deleted
        ]);
    }

    public function updateCabinPrice($checkin_id, $cabin_id, $price_value, $price2_value = null, $get_queries_string = false)
    {
        if (!$get_queries_string) {
            Pricing::where('checkin_id', $checkin_id)
                ->where('cabin_id', $cabin_id)
                ->delete();
            $price = new Pricing;
            $price->checkin_id = $checkin_id;
            $price->cabin_id = $cabin_id;
            $price->price_a = $price_value;
            if ($price2_value) {
                $price->price_b = $price2_value;
            }
            $price->save();
        } else {
            $price2_value = ($price2_value) ? "'$price2_value'" : 'NULL';
            $del_query = "DELETE FROM `mcmraak_rivercrs_pricing` WHERE `checkin_id`='$checkin_id' AND `cabin_id`='$cabin_id'";
            $ins_query = "('$checkin_id', '$cabin_id', '$price_value', $price2_value, NULL)";
            return [
                'del' => $del_query,
                'ins' => $ins_query
            ];
        }
    }

    public function updateCabinPriceQueries($del_q, $ins_q)
    {
        $del_q = array_unique($del_q);
        $ins_q = array_unique($ins_q);
        $del_q = join(';', $del_q);
        $ins_prefix = 'INSERT INTO `mcmraak_rivercrs_pricing` (`checkin_id`, `cabin_id`, `price_a`, `price_b`, `desc`) VALUES ';
        $ins_q = $ins_prefix . join(',', $ins_q) . ';';
        DB::unprepared($del_q);
        DB::unprepared($ins_q);
    }

    public function mysqlDate($date)
    {
        $date = explode('.', $date);
        return $date[2] . '-' . $date[1] . '-' . $date[0];
    }

    # http://azimut.dc/rivercrs/debug/Getter@reActualCheckins?id=init
    public function reActualCheckins()
    {
        $id = Input::get('id');
        $per_line = 30;

        if ($id == 'init') {
            $ids = $this->idsPackager(Checkin::where('active', 0)->get(), $per_line);
            $this->json($ids);
            return;
        }

        $ids = explode(',', $id);

        foreach ($ids as $checkin_id) {
            $this->reActualCheck($checkin_id);
        }

        $this->json([
            'message' => "Проверка $per_line заездов id:{$ids[0]}..." . $ids[count($ids) - 1]
        ]);
    }

    function reActualCheck($checkin_id)
    {
        $price_count = Pricing::where('checkin_id', $checkin_id)->count();
        if (!$price_count) return;

        try {
            $checkin = Checkin::find($checkin_id);
            $checkin->waybill_id = $checkin->waybill_id;
            $checkin->active = 1;
            $checkin->save();

        } catch (Exception $ex) {
            $ex->getMessage();
            JLog::add('test', 'Getter@reActualCheck', $ex->getMessage(), $checkin_id);
        }
    }

}
