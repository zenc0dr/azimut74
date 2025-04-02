<?php namespace Zen\Uongate\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Zen\Uongate\Classes\Lead as LeadClass;

class Lead extends Command
{
    protected $name = 'uongate:lead_push';
    protected $description = 'Отправить заявку в u-on';

    public function handle()
    {
        $key = $this->option('key');
        $path = temp_path("uongate/leads/$key");

        if (!file_exists($path)) {
            return;
        }

        $data = json_decode(file_get_contents($path), true);
        LeadClass::query($data);
        unlink($path);
    }

    protected function getOptions()
    {
        return [
            #ex: php artisan uongate:lead_push --key=adsjfasdjfasdfgsdfg
            ['key', null, InputOption::VALUE_OPTIONAL, 'Ключ', false],
        ];
    }
}
