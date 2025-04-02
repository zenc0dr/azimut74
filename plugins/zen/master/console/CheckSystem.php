<?php namespace Zen\Master\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CheckSystem extends Command
{
    protected $name = 'master:checksystem';

    protected $description = 'Провести проверку системы';

    public function handle()
    {
        $min = 1000;
        $size_mb = $this->getFreeSize();
        if ($size_mb < $min) {
            $text = "Внимание! На диске осталось менее $min mb";
            master()->telegram()->sendMessage($text);
        }
    }

    /**
     * Узнать свободное место
     * @return int
     */
    public function getFreeSize(): int
    {
        $size_mb = shell_exec("df -m --output=avail / | tail -n 1");
        $size_mb = trim($size_mb);
        $size_mb = intval($size_mb);
        return $size_mb;
    }
}
