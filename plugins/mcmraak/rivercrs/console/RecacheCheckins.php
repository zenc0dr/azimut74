<?php namespace Mcmraak\Rivercrs\Console;

use Illuminate\Console\Command;
use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use DB;

class RecacheCheckins extends Command
{

    protected $name = 'rivercrs:recache';
    protected $description = 'Перекеширование заездов';

    public function handle()
    {
        $all_count = DB::table('mcmraak_rivercrs_checkins')
            ->whereActive(1)
            ->count();
        $count = 0;

        DB::table('mcmraak_rivercrs_checkins')
            ->whereActive(1)
            ->orderBy('id')
            ->chunk(10, function ($checkins) use ($all_count, &$count) {
                $checkins_ids = $checkins->pluck('id')->toArray();
                foreach ($checkins_ids as $checkins_id) {
                    $count ++;
                    echo "Обработка заезда [$count из $all_count] №$checkins_id ...";
                    Checkin::getResult($checkins_id, true);
                    echo " ok\n";
                    echo "Кеширование цен ...";
                    app('Mcmraak\Rivercrs\Classes\Exist')->get($checkins_id, 'array');
                    echo " ok\n";
                }
            });
    }
}
