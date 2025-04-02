<?php namespace Zen\Keeper\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Zen\Keeper\Classes\Keeper;
use Http;
use Log;

class Backup extends Command
{

    protected $name = 'keeper:backup';

    protected $description = 'Создать бекап сайта';


    public function handle()
    {
        $remote = $this->option('remote');
        $this->cliOut('Запуск системы резервного копирования...');

        $keeper = new Keeper;
        $keeper->createBackup();

        if ($remote) {
            $remote = explode('#', $remote);
            $remote_domain = $remote[0];
            $domain = $remote[1];
            unset($remote[0]);
            unset($remote[1]);
            $security_token = join('', $remote);

            $api_url = $remote_domain
                . '/zen/keeper/api/backup:download?security_token=' . $security_token
                . "&domain=$domain";

            #Log::debug("Сигнал для скачивания бекапа [$api_url]");

            Http::get($api_url);
        }

        $this->cliOut('Резервное копирование завершено');
    }

    public function cliOut($message)
    {
        $time = date('d.m.Y H:i:s - ');
        $this->output->writeln($time . $message);
    }

    protected function getOptions()
    {
        // keeper:backup --remote=$remote_domain#$domain#$security_token

        return [
            #ex: php artisan keeper:backup --remote=http://arta-parser#http://arta-parser#jsdkfjjskdfj
            ['remote', null, InputOption::VALUE_OPTIONAL, 'Выбор парсеров', false],
        ];
    }
}
