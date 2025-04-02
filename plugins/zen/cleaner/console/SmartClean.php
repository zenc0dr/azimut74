<?php namespace Zen\Cleaner\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Zen\Cleaner\Classes\SmartCleaner;

class SmartClean extends Command
{
    protected $name = 'cleaner:smart';
    protected $description = 'Умная очистка хранилища';

    public function handle()
    {
        $cleaner = new SmartCleaner();
        $cleaner->run();
    }
}
