<?php namespace Mcmraak\Rivercrs\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use DB;
use Mcmraak\Rivercrs\Models\Checkins as Checkin;

class CheckPrices extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'rivercrs:check_prices';

    /**
     * @var string The console command description.
     */
    protected $description = 'Проверка цен';

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {

        //$this->dublePrices();
        $this->cleanPrices();
    }

    public function dublePrices()
    {
        $prices = DB::table('mcmraak_rivercrs_pricing')->get();
        $count = $prices->count();
        $key = 1;
        foreach ($prices as $price)
        {
            echo "test $key of $count: {$price->checkin_id}\r";
            $test = DB::table('mcmraak_rivercrs_pricing')
                ->where('checkin_id', $price->checkin_id)
                ->where('cabin_id', $price->cabin_id)->get();
            if($test->count() > 1)
            {
                echo "\nERROR: {$price->checkin_id}\n";
            }
            $key++;
        }
    }

    public function cleanPrices()
    {
        $prices = DB::table('mcmraak_rivercrs_pricing')->get();
        $count = $prices->count();
        $key = 1;
        foreach ($prices as $price)
        {
            echo "test $key of $count\r";
            $checkin = Checkin::find($price->checkin_id);
            if(!$checkin) {
                DB::table('mcmraak_rivercrs_pricing')
                    ->where('checkin_id', $price->checkin_id)
                    ->delete();
                echo "\nDelete prices for checkin_id:{$price->checkin_id}\n";
            }
            $key++;
        }
    }

}
