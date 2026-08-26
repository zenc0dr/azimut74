<?php namespace Zen\Uongate\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Zen\Uongate\Classes\Lead as LeadClass;
use Zen\Uongate\Classes\AmoFailAlert;

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
        try {
            LeadClass::query($data);
        } catch (\Exception $e) {
            $amo = isset($data['Amo.Integration']) && is_array($data['Amo.Integration'])
                ? $data['Amo.Integration']
                : (is_array($data) ? $data : []);
            master()->outboundHttp()->logException([
                'url' => 'https://tglk.ru/in/4PVwZs6rrSd6QRB5',
                'source' => isset($amo['source']) ? $amo['source'] : null,
                'payload' => $amo,
            ], $e);
            AmoFailAlert::send($amo);
        }
        if (file_exists($path)) {
            unlink($path);
        }
    }

    protected function getOptions()
    {
        return [
            #ex: php artisan uongate:lead_push --key=adsjfasdjfasdfgsdfg
            ['key', null, InputOption::VALUE_OPTIONAL, 'Ключ', false],
        ];
    }
}
