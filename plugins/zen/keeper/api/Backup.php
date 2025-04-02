<?php namespace Zen\Keeper\Api;

use Zen\Keeper\Classes\Core;
use Input;
use Zen\Keeper\Models\Settings;
use Zen\Keeper\Classes\Cli;
use Zen\Keeper\Models\Site;
use Zen\Keeper\Models\Backup as BackupModel;
use Response;
use BackendAuth;
use Log;

class Backup extends Core
{

    # http://arta-parser/zen/keeper/api/backup:make
    public function make()
    {
        $security_token = Input::get('security_token');
        $remote_domain = Input::get('remote_domain');

        if ($security_token !== Settings::get('security_token')) {
            $this->json([
                'status' => 'error',
                'error' => 0,
                'message' => 'Invalid security token',
            ]);
            return;
        }

        $cli = new Cli;

        if ($cli->processIsLaunched('keeper:buckup')) {
            $this->json([
                'status' => 'error',
                'error' => 1,
                'message' => 'Процесс не завершён'
            ]);
            return;
        } else {
            $domain = $this->getDomain();

            $command = "keeper:backup --remote=$remote_domain#$domain#$security_token";

            #Log::debug("keeper cli command: $command");

            $cli->artisanExec($command);
        }

        $this->json([
            'status' => 'success',
            'message' => "Запрос на создание резервной копии [$domain] принят!"
        ]);
    }

    # http://arta-parser/zen/keeper/api/backup:download?security_token=xxxxxxxx&domain=http://arta-parser
    public function download()
    {
        $remote_security_token = Input::get('security_token');
        $remote_domain = Input::get('domain');
        $site = Site::where('url', $remote_domain)->where('security_token', $remote_security_token)->first();

        #Log::debug("remote_security_token=$remote_security_token remote_domain=$remote_domain");

        if (!$site) {
            #Log::debug("Сайт не обнаружен");
            return;
        }

        $cli = new Cli;

        $cli->artisanExec("keeper:download --url=$remote_domain");
    }

    #  http://arta-parser/zen/keeper/api/backup:get?security_token=xxxxxxxx
    public function get()
    {
        $security_token = Input::get('security_token');
        if ($security_token != Settings::get('security_token')) {
            die('error');
        }
        $backup_path = temp_path('keeper_backup/backup.zip');
        return Response::download($backup_path);
    }

    # http://arta-parser/zen/keeper/api/backup:url?backup_id=1
    public function url()
    {
        if (!BackendAuth::check()) {
            return 'Access error';
        }

        $backup_id = Input::get('backup_id');
        $backup_path = storage_path("keeper_buckups/backup_id_$backup_id.zip");
        $backup = BackupModel::find($backup_id);

        $file_name = $backup->site->url;
        $file_name = str_replace('https://', '', $file_name);
        $file_name = str_replace('http://', '', $file_name);
        $file_name = str_replace('.', '_', $file_name);
        $file_name .= '--' . $backup->created_at->format('d-m-y_H-i-s') . '.zip';

        return Response::download($backup_path, $file_name);
    }
}
