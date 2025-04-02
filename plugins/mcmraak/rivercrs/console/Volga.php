<?php namespace Mcmraak\Rivercrs\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class Volga extends Command
{
    protected $name = 'rivercrs:volga';
    protected $description = 'Обслуживание источника "Волга-Волга"';

    function handle()
    {
        $action = $this->option('action');

        if($action == 'getDb') {
            $this->getDb();
        }
    }

    # cron command: /opt/alt/php73/usr/bin/php /var/www/user134742/data/www/azimut/artisan rivercrs:volga --action=getDb > /dev/null 2>&1
    function getDb()
    {
        /*
         * Выгрузка круизов навигации 2021 доступна по адресу
           http://test.volgawolga.ru/php/xml/index.php
         *
         *
         * */
        # http://www.volgawolga.ru/php/xml/ --> storage/volga-db-short.php
        # http://www.volgawolga.ru/php/xml/index-tst.php -> storage/volga-db.php

        # Новая короткая http://test.volgawolga.ru/php/xml/index.php
        # Новая полная http://test.volgawolga.ru/php/xml/index-tst.php

        #$url_short = 'http://www.volgawolga.ru/php/xml';
        #$url_full = 'http://www.volgawolga.ru/php/xml/index-tst.php';

        $url_short = 'http://test.volgawolga.ru/php/xml/index.php';
        $url_full =  'http://test.volgawolga.ru/php/xml/index-tst.php';

        $storage_path_short = base_path('storage/volga-db-short.php');
        $storage_path_full = base_path('storage/volga-db.php');

        `wget -O $storage_path_short '$url_short' >/dev/null &`;
        `wget -O $storage_path_full '$url_full' >/dev/null &`;
    }

    protected function getOptions()
    {
        return [
            ['action', null, InputOption::VALUE_OPTIONAL, 'Метод', false],
        ];
    }
}
