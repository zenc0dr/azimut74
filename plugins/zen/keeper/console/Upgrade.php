<?php namespace Zen\Keeper\Console;

use Illuminate\Console\Command;
use Zen\Keeper\Models\Site;
use Illuminate\Filesystem\Filesystem;
use October\Rain\Filesystem\Zip;
use Http;

class Upgrade extends Command
{
    protected $name = 'keeper:upgrade';
    protected $description = 'Обновить клиенты';

    public function handle()
    {
        //$this->output->writeln('Hello world!');


        $self_path = base_path('plugins/zen/keeper');
        $temp_path = temp_path('keeper_plugin_temp');
        $upload_zip = temp_path('keeper_plugin.zip');

        $fileSystem = new Filesystem;
        $fileSystem->copyDirectory($self_path, $temp_path);
        Zip::make($upload_zip, $self_path . '/*');

        $postData = [
            'new_version' => file_get_contents($upload_zip)
        ];


        $sites = Site::where('active', 1)->get();

        foreach ($sites as $site) {
            $url = "$site->url/zen/keeper/api/service:upgrade";
            $postData['security_token'] = $site->security_token;
            $response = Http::post($url, function ($http) use ($postData) {
                $http->data($postData);
            });
            $this->output->writeln($site->url." - Клиент обновлён [$response]");
        }

        $fileSystem->deleteDirectory($temp_path, false);
        unlink($upload_zip);

    }

}
