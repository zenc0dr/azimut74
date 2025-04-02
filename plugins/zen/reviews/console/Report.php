<?php namespace Zen\Reviews\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class Report extends Command
{
    protected $name = 'reviews:report';
    protected $description = 'Послать отчёт';

    public function handle()
    {
        $data = json_decode($this->option('json_data'), 1);
        \Http::post('https://tglk.ru/in/5nJ6Rix6nUzXMeq5', function ($http) use ($data) {
            $http->data($data);
        });
    }

    protected function getOptions()
    {
        return [
            ['json_data', null, InputOption::VALUE_OPTIONAL, 'Посылаемые данные', false],
        ];
    }
}
