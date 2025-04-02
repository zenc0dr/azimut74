<?php namespace Zen\Dolphin\Console;

use Illuminate\Console\Command;
use DB;

class ClearOld extends Command
{
    protected $name = 'dolphin:clearold';
    protected $description = 'Удалить устаревшее';

    public function handle()
    {
        DB::table('zen_dolphin_prices')->where('date', '<', now())->delete();
        echo "Устаревшие данные удалены\n";
    }
}
