<?php namespace Zen\Worker\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Zen\Worker\Classes\Dispatcher;

class Go extends Command
{
    protected $name = 'worker:go';
    protected $description = 'Запуск очереди задач';

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        //return; # Временно отключено
        Dispatcher::run();
    }

    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     * @return array
     */
    protected function getOptions()
    {
        return [];
    }
}
