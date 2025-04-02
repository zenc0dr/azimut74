<?php namespace Mcmraak\Rivercrs\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use DB;
use Mcmraak\Rivercrs\Models\Checkins as Checkin;

class Check extends Command
{

    protected $name = 'rivercrs:service';
    protected $description = 'Необходимые проверки';

    # http://azimut.dc/rivercrs/debug/Console/Check@handle
    public function handle()
    {
        $this->fixZeroPrices();
        $this->fixCheckins();
    }

    function fixCheckins()
    {
        $checkins_count = DB::table('mcmraak_rivercrs_checkins')->count();
        $iteration = 0;
        $found = 0;
        $activated = 0;
        $deactivated = 0;
        DB::table('mcmraak_rivercrs_checkins')
            ->orderBy('id')
            ->chunk(100, function ($records) use (
                $checkins_count,
                &$iteration,
                &$found,
                &$activated,
                &$deactivated
            ) {

                foreach ($records as $record) {
                    $iteration++;
                    echo "Заезд #$record->id [$iteration из $checkins_count]: ";

                    $prices = DB::table('mcmraak_rivercrs_pricing')
                        ->where('checkin_id', $record->id)
                        ->get();


                    if (!$prices->count()) {
                        if ($record->active) {
                            $found++;
                            DB::table('mcmraak_rivercrs_checkins')
                                ->where('id', $record->id)
                                ->update([
                                    'active' => 0
                                ]);
                            echo " Деактиваровано [$record->eds_code]\n";
                            $deactivated++;
                        } else {
                            echo " OK\n";
                        }
                    } else {
                        if (!$record->active) {
                            $found++;
                            DB::table('mcmraak_rivercrs_checkins')
                                ->where('id', $record->id)
                                ->update([
                                    'active' => 1
                                ]);
                            echo " Активаровано [$record->eds_code]\n";
                            $activated++;
                        } else {
                            echo " OK\n";
                        }
                    }
                }
            });

        echo "Проверка закончена, исправлено рейсов: $found, Активировано: $activated, Деактивировано: $deactivated\n";
    }

    function fixZeroPrices()
    {
        $count = DB::table('mcmraak_rivercrs_pricing')->where('price_a', 0)->count();
        DB::table('mcmraak_rivercrs_pricing')->where('price_a', 0)->delete();
        echo "Удалено нулевых цен: $count\n";
    }


}
