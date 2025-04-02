<?php namespace Zen\Dolphin\Console;

use Illuminate\Console\Command;
use Zen\Cabox\Classes\Cabox;

class Gc extends Command
{
    protected $name = 'dolphin:gc';

    protected $description = 'Dolphin Garbage Collector';

    public function handle()
    {
        $this->output->writeln('Начинаю сбурку мусора');
        $this->cleanCabox();
    }

    # Очистка кеша
    function cleanCabox()
    {
        $time_now = time(); # 86400 - Сутки / 864000 10 Суток


        $cache = new Cabox('dolphin.parsers');

        $output = 0;
        $deleted = 0;

        $cache->handleItems(function ($item) use (&$output, &$deleted, $time_now, $cache) {
            $time = $item['time'];
            $key = $item['key'];

            $time_elapsed = $time_now - $time;

            if(strpos($key, 'dolpin.tour.id#') === 0 || strpos($key, 'dolpin.hotel.id#') === 0) {

                if($time_elapsed > 864000) {
                    $cache->del($key);
                    $deleted++;
                }
            } else {
                if($time_elapsed > 85000) {
                    $cache->del($key);
                    $deleted++;
                }
            }

            $output++;
        });


        $cache = new Cabox('dolphin.search');

        $cache->handleItems(function ($item) use (&$output, &$deleted, $time_now, $cache) {
            $time = $item['time'];
            $key = $item['key'];

            $time_elapsed = $time_now - $time;

            if($time_elapsed > 85000) {
                $cache->del($key);
                $deleted++;
            }

            $output++;
        });




        $this->output->writeln("Сборщик мустора завершил работу");
        $this->output->writeln("Обработано: $output записей");
        $this->output->writeln("Удалено: $deleted записей");

    }

}
