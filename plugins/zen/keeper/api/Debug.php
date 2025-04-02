<?php namespace Zen\Keeper\Api;

use Zen\Keeper\Classes\Keeper;
use Input;
use Log;
use Http;
use Illuminate\Support\Facades\Artisan;
use Zen\Keeper\Classes\CloudMailRu;
use Illuminate\Filesystem\Filesystem;
use BackendAuth;
use Zen\Keeper\Console\Go;

class Debug
{
    public function __construct()
    {
        if (!BackendAuth::check()) {
            die('access error');
        }
    }

    # http://arta-parser/zen/keeper/api/debug:testBackup
    public function testBackup()
    {
        $keeper = new Keeper;
        $keeper->createBackup();
    }

    # http://arta-parser/zen/keeper/api/debug:testHttpQuery
    public function testHttpQuery()
    {
        $answer = Http::get('http://arta-parser/zen/keeper/api/debug:testHttpAnswer');
        echo $answer->body;
    }

    # http://arta-parser/zen/keeper/api/debug:testHttpAnswer
    public function testHttpAnswer()
    {
        echo 'answer';
    }

    # http://arta-parser/zen/keeper/api/debug:testBackupMake
    public function testBackupMake()
    {
        $domain = env('APP_URL');
        $domain = preg_replace('/\/$/', '', $domain);
        $api_url = 'https://gorod-tc.ru'
            . '/zen/keeper/api/backup:make?security_token=' . 'HDTV4-BVSTV-PR5NF-GVFR3-F4AAQ'
            . "&remote_domain=$domain";

        dd($api_url);

        $response = Http::get($api_url);
        $response = json_decode($response->body, true);
    }

    # http://arta-parser/zen/keeper/api/debug:testGo
    public function testGo()
    {
        Artisan::call('keeper:go');
    }

    # http://arta-parser/zen/keeper/api/debug:testUploadMailRuCloud
    public function testUploadMailRuCloud()
    {
        $cloud = new CloudMailRu('mraak@mail.ru', 'h6Gbdg#hgBiOl');
        if ($cloud->login()) {

            $file = storage_path('test_dump.sql.gz');
            $file_cloud = '/test_dump.sql.gz';

            $url = $cloud->loadFileAhdPublish($file, $file_cloud);

            if ($url !== "error") {
                echo 'ссылка для скачивания - ' . $url;
            } else {
                echo 'загрузка в облако не удалась';
            }

        } else {
            echo 'не прошли авторизацию';
        }

        unset($cloud);
    }

    # http://arta-parser/zen/keeper/api/debug:testHiddenFiles
    public function testHiddenFiles()
    {
        $fileSystem = new Filesystem;
        $base_files = $fileSystem->files(base_path(), 1);

        foreach ($base_files as $file) {
            echo $path = $file->getPathname() . "<br>";
        }
    }

    # http://arta-parser/zen/keeper/api/debug:testRotation
    public function testRotation()
    {
        (new Go)->rotation();
    }
}
