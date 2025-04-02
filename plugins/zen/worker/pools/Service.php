<?php namespace Zen\Worker\Pools;

use Mcmraak\Rivercrs\Classes\CacheSettings;
use Mcmraak\Rivercrs\Classes\Search;
use Mcmraak\Rivercrs\Models\Cabins as Cabin;
use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use Mcmraak\Rivercrs\Models\Motorships as Ship;
use Mcmraak\Rivercrs\Models\Pricing;
use Mcmraak\Rivercrs\Models\Waybills as Waybill;
use Zen\Worker\Classes\ProcessLog;
use Carbon\Carbon;
use DB;

class Service extends RiverCrs
{
    public function fixTimeoutCheckins()
    {
        $old_checkins_ids = DB::table('mcmraak_rivercrs_checkins')
            ->whereNotNull('eds_code')
            ->where('date', '<', Carbon::now()->subDay())
            ->select('id')
            ->pluck('id')
            ->toArray();

        foreach ($old_checkins_ids as $id) {
            $this->stream->addJob('Service@removeCheckin', ['id' => (int)$id]);
        }
    }

    public function shipCleaner()
    {
        # Чистка теплоходов
        $ids = CacheSettings::getBadShips();
        foreach ($ids as $data) {
            $ship_id = $data[0];
            $eds_name = $data[1];
            $ship = Ship::find($ship_id);
            if (!$ship) {
                continue;
            }
            if (!$ship->{$eds_name . '_id'}) {
                continue;
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
        }
    }

    public function emptyPrices()
    {
        # Удаление цен с пустыми заездами
        $to_remove = DB::table('mcmraak_rivercrs_pricing')->where('checkin_id', 0)->get();
        foreach ($to_remove as $record) {
            $this->stream->addJob('Service@removeCheckin', ['id' => (int)$record->checkin_id]);
        }
    }

    public function noPricesCabins()
    {
        # Удаление ненужных кают
        DB::table('mcmraak_rivercrs_cabins')->orderBy('id')->chunk(100, function ($cabins) {
            foreach ($cabins as $cabin) {
                $test = $this->isCabinNotLet($cabin->category, $cabin->motorship_id);
                if ($test) {
                    Cabin::find($cabin->id)->delete();
                    ProcessLog::add("Удаление каюты id:{$cabin->id}");
                }
            }
        });
    }

    public function notActualCheckins()
    {
        DB::unprepared("SET sql_mode = ''");
        $checkins_ids = DB::table('mcmraak_rivercrs_waybills')->groupBy('checkin_id')->pluck('checkin_id')->toArray();
        foreach ($checkins_ids as $checkin_id) {
            $checkin = Checkin::find($checkin_id);
            if (!$checkin) {
                ProcessLog::add("Удаляются маршруты для несуществующего заезда id:$checkin_id");
                DB::table('mcmraak_rivercrs_waybills')->where('checkin_id', $checkin_id)->delete();
            }
        }
    }

    public function noCabinPrices()
    {
        # Удаление цен без кают
        DB::table('mcmraak_rivercrs_pricing')->orderBy('checkin_id')->chunk(100, function ($prices) {
            foreach ($prices as $price) {
                $cabin = Cabin::find($price->cabin_id);
                if (!$cabin) {
                    ProcessLog::add("Удаляются цены для несуществующей каюты id:{$price->cabin_id}");
                    DB::table('mcmraak_rivercrs_pricing')->where('checkin_id', $price->checkin_id)
                        ->where('cabin_id', $price->cabin_id)
                        ->delete();
                }
            }
        });
    }

    public function removeCheckin($data)
    {
        $id = $data['id'];
        DB::table('mcmraak_rivercrs_checkins_memory')->where('checkin_id', $id)->delete();
        $checkin = Checkin::find($id);
        if (!$checkin) {
            return;
        }
        ProcessLog::add("Удаление заезда id:$id");
        $checkin->delete();
    }

    public function successCruisesClean()
    {
        $checkins = DB::table('mcmraak_rivercrs_checkins_memory')
            ->where('updated_at', '<', Carbon::now()->subWeek())
            ->get();

        foreach ($checkins as $checkin) {
            $this->stream->addJob('Service@removeCheckin', ['id' => intval($checkin->checkin_id)]);
        }
    }

    public function analyzeCheckinsCache()
    {
        $checkin_ids = Checkin::whereActive(1)
            ->select('id')
            ->pluck('id')
            ->toArray();

        $memory_ids = DB::table('mcmraak_rivercrs_checkins_memory')
            ->select('checkin_id')
            ->pluck('checkin_id')
            ->toArray();

        $diff_ids = array_diff($checkin_ids, $memory_ids);

        foreach ($diff_ids as $checkin_id) {
            $this->stream->addJob('Service@createCheckinCache', ['id' => intval($checkin_id)]);
        }
    }

    public function createCheckinCache($data)
    {
        $checkin_id = $data['id'];
        $this->renderCheckin($checkin_id, ['update' => true]);
    }

    public function fixCheckinsPrices()
    {
        DB::table('mcmraak_rivercrs_pricing')->where('price_a', 0)->delete();
        DB::table('mcmraak_rivercrs_checkins')
            ->orderBy('id')
            ->chunk(100, function ($records) {
                foreach ($records as $record) {
                    $prices = DB::table('mcmraak_rivercrs_pricing')
                        ->where('checkin_id', $record->id)->get();
                    if (!$prices->count()) {
                        DB::table('mcmraak_rivercrs_checkins')
                            ->where('id', $record->id)
                            ->update([
                                'active' => 0
                            ]);
                    } else {
                        DB::table('mcmraak_rivercrs_checkins')
                            ->where('id', $record->id)
                            ->update([
                                'active' => 1
                            ]);
                    }
                }
            });
    }

    public function checkData()
    {
        \Artisan::call('rivercrs:check_data');
    }
}
