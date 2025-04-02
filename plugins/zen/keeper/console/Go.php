<?php namespace Zen\Keeper\Console;

use Illuminate\Console\Command;
use Zen\Keeper\Models\Site;
use Zen\Keeper\Models\Backup;
use Http;
use Zen\Keeper\Models\Settings;

class Go extends Command
{
    protected $name = 'keeper:go';
    protected $description = 'Сделать резервные копии всех сайтов';

    public function handle()
    {
        $domain = env('APP_URL');
        $domain = preg_replace('/\/$/', '', $domain);

        $sites = Site::where('active', 1)->get();

        foreach ($sites as $site) {

            $api_url = $site->url
                . '/zen/keeper/api/backup:make?security_token=' . $site->security_token
                . "&remote_domain=$domain";

            $response = Http::get($api_url);
            $response = json_decode($response->body, true);

            if(@$response['status'] == 'success') {
                $this->output->writeln($site->url . ': ok');
            } else {
                $this->output->writeln($site->url . ': error');
            }

            $time_interval = Settings::get('time_interval');
            if($time_interval) sleep($time_interval);

        }

        $this->rotation();
    }

    function rotation()
    {
        $backups = Backup::orderBy('created_at')->get();
        $all_size = $backups->sum('size');

        $size_limit = intval(Settings::get('size_limit')); # В мегабайтах
        $size_limit *= 1048576; # В байтах

        if($all_size < $size_limit) return;

        foreach ($backups as $backup) {
            if($all_size < $size_limit) break;
            $backup_size = $backup->size;
            $all_size -= $backup_size;
            $backup->delete();
        }
    }
}
