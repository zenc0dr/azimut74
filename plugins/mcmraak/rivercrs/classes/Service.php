<?php namespace Mcmraak\Rivercrs\Classes;

use Mcmraak\Rivercrs\Classes\CacheSettings;
use Mcmraak\Rivercrs\Classes\Parser;
use Mcmraak\Rivercrs\Classes\ParserLog;
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
use Mcmraak\Rivercrs\Traits\Volga;
use Mcmraak\Rivercrs\Traits\Waterway;
use System\Models\File;
use DB;
use Input;
use Carbon\Carbon;
use Mcmraak\Rivercrs\Classes\Idmemory as ID;
use Mcmraak\Rivercrs\Models\Log as JLog;
use Mcmraak\Rivercrs\Classes\Ids;
use System\Helpers\Cache;
use Config;
use App;
use Queue;
use View;

class Service extends Parser
{
    # http://azimut.dc/rivercrs/debug/Service@removeEdsData?eds_name=infoflot
    function removeEdsData()
    {
        $eds_name = Input::get('eds_name');

        $eds_names = [
            'waterway',
            'volga',
            'gama',
            'germes',
            'infoflot',
        ];

        if (!array_search($eds_name, $eds_names)) {
            echo 'Источник не существует';
        }

        $other_ships_eds_ids = [];
        foreach ($eds_names as $name) {
            if ($name == $eds_name) {
                continue;
            }
            $other_ships_eds_ids[] = $name . '_id';
        }

        //dd($other_ships_eds_ids);
        $records = Checkin::where('eds_code', $eds_name)->get();

        $count = 0;
        foreach ($records as $record) {
            $job_exist = DB::table('jobs')
                ->where('queue', 'RiverCrs.recacheChekin')
                ->where('payload', 'like', '%"checkin_id":' . $record->id . '%')
                ->count();
            if (!$job_exist) {
                app('\Mcmraak\Rivercrs\Classes\Search')->delCache($record->id);
                $count++;
                Queue::push('\Mcmraak\Rivercrs\Controllers\Checkins@deleteOldCheckinJob', [
                    'checkin_id' => $record->id
                ], 'RiverCrs.removeChekin');
            }
        }

        echo "В очередь поставлено: {$count} заданий на удаление";
    }

    # http://azimut.dc/rivercrs/debug/Service@printLogs
    function printLogs()
    {
        echo View::make('mcmraak.rivercrs::logs', ['items' => JLog::where('type', 'error')->get()])->render();
    }


    # http://azimut.dc/rivercrs/debug/Service@getParserLog?eds=infoflot
    # http://azimut.dc/rivercrs/debug/Service@getParserLog?eds=infoflot&show_all
    public function getParserLog()
    {
        $eds_code = Input::get('eds');
        $show_all = (Input::get('show_all') !== null) ? true : false;
        if ($show_all) {
            $logs = JLog::where('eds_code', $eds_code)->get();
        } else {
            $logs = JLog::where('eds_code', $eds_code)->where('type', 'error')->get();
        }

        echo View::make('mcmraak.rivercrs::logs', ['items' => $logs, 'eds_code' => $eds_code, 'show_all' => $show_all])->render();
    }


    # http://azimut.dc/rivercrs/debug/Service@checkDecks
    function checkDecks()
    {
        $decks = Deck::orderBy('id')->get();
        foreach ($decks as $deck) {
            $this->testDeck($deck->id);
            $this->checkParentDecks($deck->id);
        }
    }

    function testDeck($deck_id)
    {
        $deck = Deck::find($deck_id);
        if (!$deck) {
            return;
        }

        $other_decks = Deck::where('name', $deck->name)->where('id', '<>', $deck->id)->orderBy('id')->get();
        if (!$other_decks->count()) {
            return;
        }
        $pt = 'mcmraak_rivercrs_decks_pivot';
        if ($other_decks->count()) {
            foreach ($other_decks as $other_deck) {
                echo "Удаляю палубу id:{$other_deck->id}<br>";
                DB::table('mcmraak_rivercrs_decks')->where('id', $other_deck->id)->delete();
                $pivots = DB::table($pt)->where('deck_id', $other_deck->id)->get();
                echo "Нахожу пивоты: where deck_id={$other_deck->id} " . $pivots->count() . '<br>';
                foreach ($pivots as $record) {
                    echo "Удаляю пивот: cabin_id={$record->cabin_id} deck_id={$other_deck->id}<br>";
                    DB::table($pt)->where('cabin_id', $record->cabin_id)->where('deck_id', $other_deck->id)->delete();
                    $test_count = DB::table($pt)->where('cabin_id', $record->cabin_id)->where('deck_id', $deck->id)->count();
                    if (!$test_count) {
                        echo "Создаю пивот: cabin_id={$record->cabin_id} deck_id={$deck->id}<br>";
                        DB::table($pt)->insert(['cabin_id' => $record->cabin_id, 'deck_id' => $deck->id]);
                    }
                }
            }
        }
    }

    function checkParentDecks($deck_id)
    {
        $deck = Deck::find($deck_id);
        if (!$deck) {
            return;
        }
        if (!$deck->parent_id) {
            return;
        }

        $invalids = DB::table('mcmraak_rivercrs_decks_pivot')->where('deck_id', $deck->id)->get();
        if ($invalids->count()) {
            DB::table('mcmraak_rivercrs_decks_pivot')
                ->where('deck_id', $deck->id)
                ->update([
                    'deck_id' => $deck->parent_id
                ]);
            echo "Замена инвалидных записей {$deck->id} >> {$deck->parent_id} [{$invalids->count()}]<br>";
        }
    }



    # http://azimut.dc/rivercrs/debug/Service@idsViewer?like_id=ship_tour:
    # http://azimut.dc/rivercrs/debug/Service@idsViewer?stats
    function idsViewer()
    {
        $stats = (Input::get('stats') !== null) ? true : false;

        $cache_db = new Ids('infoflot_cache', CacheSettings::get('infoflot_tours'));

        if ($stats) {
            $stats = $cache_db->stats();
            dd($stats);
        }

        $like_id = Input::get('like_id');

        $records = $cache_db->like($like_id);
        dd($records);
    }

    # http://azimut.dc/rivercrs/debug/Service@idsDelete?like_id=cruise_page&preview
    # http://azimut.dc/rivercrs/debug/Service@idsDelete?only=cruise_page:1
    # http://azimut.dc/rivercrs/debug/Service@idsDelete?clean
    function idsDelete()
    {
        $preview = (Input::get('preview') !== null) ? true : false;
        $like_id = Input::get('like_id');
        $only = Input::get('only');
        $clean = (Input::get('clean') !== null) ? true : false;

        $cache_db = new Ids('infoflot_cache', CacheSettings::get('infoflot_tours'));

        if ($clean) {
            $cache_db->clean();
            die('is clean');
        }

        if ($only) {
            $cache_db->delete($only);
            return;
        }

        $cache_db->deleteLike($like_id, $preview);
    }


    # http://azimut.dc/rivercrs/debug/Service@getVolgaDB
    function getVolgaDB()
    {
        $path = "storage/next_url.xml";
        echo file_get_contents(base_path($path));
    }

    # http://azimut.dc/rivercrs/debug/Service@getStats?key=eSw2bHdi8GbdhfmDf4VfhHhdbrgtJd
    function getStats()
    {
        $key = get('key');

        if ($key != 'eSw2bHdi8GbdhfmDf4VfhHhdbrgtJd') {
            die;
        }

        $orders = DB::table('mcmraak_rivercrs_booking')
            ->whereNotNull('order')
            ->get();
        $out = [];
        $i = 0;
        foreach ($orders as $record) {
            $order = json_decode($record->order, true);

            $price = 0;

            foreach ($order['cabins'] as $cabin) {
                foreach ($cabin['prices'] as $cabin_price) {
                    $price += $cabin_price['price_value'];
                }
            }

            if ($price) {
                $add = [
                    'name' => $order['name'],
                    'phone' => $order['phone'],
                    'email' => $order['email'],
                    'date' => $order['date'],
                    'price' => $price
                ];

                if ($this->checkNonUniq($add, $out)) {
                    $out[] = $add;
                }
            }

            $i++;
        }

        echo View::make('mcmraak.rivercrs::stats', ['records' => $out])->render();
    }

    function checkNonUniq($add, &$out)
    {
        if (!$out) {
            return true;
        }

        foreach ($out as $item) {
            if ($item['phone'] == $add['phone'] && $item['price'] == $add['price']) {
                return false;
            }
        }

        return true;
    }
}
