<?php namespace Zen\Keeper\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Zen\Keeper\Models\Site;
use Zen\Keeper\Models\Backup;
use Log;

class Download extends Command
{
    protected $name = 'keeper:download';
    protected $description = 'Скачивает удалённый бекап';

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        $url = $this->option('url');

        $site = Site::where('url', $url)->first();

        if (!$site) {
            return;
        }

        $api_url = $url . '/zen/keeper/api/backup:get?security_token=' . $site->security_token;
        $backup = new Backup;
        $backup->site_id = $site->id;
        $backup->save();

        $backups_path = storage_path('keeper_buckups');
        if (!file_exists($backups_path)) {
            mkdir($backups_path, 0777);
        }

        $backups_path .= "/backup_id_{$backup->id}.zip";

        $command = "wget -O $backups_path '$api_url' --no-check-certificate";

        #Log::debug("keeper cli command: $command");

        exec($command);

        $backup->size = filesize($backups_path);
        $backup->save();
    }

    protected function getOptions()
    {
        return [
            #ex: php artisan keeper:download --url=http://arta-parser
            ['url', null, InputOption::VALUE_OPTIONAL, 'Выбор парсеров', false],
        ];
    }
}
