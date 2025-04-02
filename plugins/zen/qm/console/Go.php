<?php namespace Zen\Qm\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Zen\Qm\Models\Job;

class Go extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'qm:go';

    /**
     * @var string The console command description.
     */
    protected $description = 'Запустить очередь задач';

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        $jobs = Job::get();
        $cnt = $jobs->count();
        $i=0;
        foreach ($jobs as $job) {
            $i++;
            $payload = json_decode($job->payload);
            $class = explode('@', $payload->job);
            echo "Задача [$i из $cnt]: {$class[0]}@{$class[1]}: ";
            app($class[0])->{$class[1]}($job, (array) $payload->data);
            echo "Выполнена\n";
        }
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
