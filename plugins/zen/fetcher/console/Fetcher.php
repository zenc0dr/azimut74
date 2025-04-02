<?php namespace Zen\Fetcher\Console;

use Illuminate\Console\Command;

/**
 * Консольный лаунчер для модуля Reviews
 * ex: php artisan fetcher folder.Class.method - Запуск Zen\Fetcher\Console\folder\Class@method
 */

class Fetcher extends Command
{
    protected $signature = 'fetcher {path} {--input=}';
    protected $description = 'Консольное api для Fetcher';

    public function handle()
    {
        $path = $this->argument('path');
        $input = $this->option('input');
        fetcher()->console($path, $input);
    }
}
