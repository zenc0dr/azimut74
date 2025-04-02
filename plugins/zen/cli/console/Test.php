<?php namespace Zen\Cli\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class Test extends Command
{
    protected $name = 'cli:test';
    protected $description = 'Тестирование плгина';

    public function handle()
    {
        $time = $this->option('time') ?? 10;

        $this->output->writeln("Запускаю процесс $time сек");


        for($i=0; $i<$time; $i++) {
            $ii = $i+1;
            sleep(1);
            echo "Секунд прошло: $ii из $time\r";
        }


        $this->output->writeln("Процесс завершён");
    }

    protected function getOptions()
    {
        return [
            ['time', null, InputOption::VALUE_OPTIONAL, 'Время жизни процесса (сек) ex: cli:test --time=10', null],
        ];
    }
}
