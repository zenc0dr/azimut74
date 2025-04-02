<?php namespace Zen\Dolphin\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Zen\Dolphin\Classes\Core;

class Search extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'dolphin:search';

    protected $description = 'Поток поискового запроса';

    public function handle()
    {
        $token = $this->option('token');
        (new Core)->stream($token, 'dolphin.search')->run();
    }

    protected function getOptions()
    {
        return [
            #ex: php artisan dolphin:search --token={token}
            ['token', null, InputOption::VALUE_REQUIRED, 'Ключ поиска', false],
        ];
    }
}
