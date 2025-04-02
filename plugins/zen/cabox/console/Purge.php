<?php namespace Zen\Cabox\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Zen\Cabox\Models\Storage;
use Exception;

class Purge extends Command
{
    protected $name = 'cabox:purge';
    protected $description = 'Clean storage files';

    public function handle()
    {
        $code = $this->option('code');

        if(!$code) {
            $this->output->writeln('Не указан код хранилища --code=');
            return;
        }

        $storage = Storage::where('code', $code)->first();
        $result = $storage->purge();

        if($result instanceof Exception) {
            $this->output->writeln("Error: {$result->getMessage()} in file {$result->getFile()}:{$result->getLine()}");
        }

        $this->output->writeln('Cleared!');
    }

    protected function getOptions()
    {
        $help = 'clean storage by code';
        return [
            #ex: php artisan cabox:purge --code=storagecode
            ['code', null, InputOption::VALUE_OPTIONAL, $help, false],
        ];
    }
}
