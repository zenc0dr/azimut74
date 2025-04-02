<?php namespace Mcmraak\Rivercrs\Console;

use Illuminate\Console\Command;
use Mcmraak\Rivercrs\Classes\CacheSettings;
use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use DB;

class Fixmaster extends Command
{
    protected $name = 'rivercrs:fixmaster';
    protected $description = 'No description provided yet...';

    public function handle()
    {
        DB::table('mcmraak_rivercrs_checkins')
            ->orderBy('id')
            ->chunk(100, function ($records) {
                foreach ($records as $record) {
                    $this->fixCheckin(Checkin::find($record->id));
                }
            });
    }

    private function fixCheckin(Checkin $checkin) {
        $is_bad = $this->shipCheck($checkin);
        if($is_bad) {
            $checkin->delete();
        }
    }

    private function shipCheck(Checkin $checkin)
    {
        if(!$checkin->eds_code) return;
        $ship_name = $checkin->motorship->name;
        $ship_name = trim(str_replace('Теплоход', '', $ship_name));
        $ship_name = str_replace('"', '', $ship_name);
        if(CacheSettings::shipIsBad($ship_name, $checkin->eds_code)) {
            echo "Будет удалён заезд: $checkin->id [$ship_name]\n";
            return true;
        }
    }
}
