<?php namespace Zen\Worker\Classes;

use BackendAuth;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Zen\Cabox\Classes\Cabox;
use Zen\Cli\Classes\Cli;
use Zen\Worker\Models\Stream;
use Zen\Worker\Models\ErrorLog;
use DB;
use October\Rain\Filesystem\Zip;
use Input;

class Core
{
    # Проверка, является ли пользователь администратором
    function isAdmin($return = false)
    {
        if(!BackendAuth::check()) {
            if($return) return false;
            die('Access denied');
        }
        return true;
    }

    function input($key, $options = null)
    {
        $value = Input::get($key);

        if(is_string($value)) $value = trim($value);

        if(is_string($options)) {
            $options = [$options];
        }

        if($options) {
            foreach ($options as $option) {
                if($option == 'only_digits') {
                    $value = preg_replace('/\D/','', $value);
                }
                if($option == 'bool') {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                }
                if($option == 'int') {
                    $value = intval($value);
                }
            }
        }

        return $value;
    }

    # Преобразовать массив в json-строку
    function json($array, $return=false)
    {
        $json_string = json_encode($array, JSON_UNESCAPED_UNICODE);
        if($return) return $json_string;
        echo $json_string;
    }


    ##### Функции Cli
    # Отправить команду artisan в фон для выполнения
    function artisanExec($artisan_command)
    {
        $cli = new Cli;
        $cli->nohup = true;
        $command = $cli->artisanExec($artisan_command);
    }

    # Проверить запущен ли процесс
    function processIsLaunched($entry) {
        $cli = new Cli;
        return $cli->processIsLaunched($entry);
    }

    # Завершить процесс
    function killProcess($entry)
    {
        $cli = new Cli;
        $cli->killProcess($entry);
    }
    ###############


    static function getStates() {
        $streams = Stream::active()->get();
        $output = [];
        foreach ($streams as $stream) {
            $pools = $stream->pools;
            $stateArr = $stream->getState();

            if(!$pools || !$stateArr) continue;

            $state = [];
            $i = 0;
            foreach ($pools as $pool) {
                $state[] = array_merge($pool, $stateArr[$i]);
                $i++;
            }
            $output[] = [
                'name' => $stream->name,
                'code' => $stream->code,
                'state' => $state
            ];
        }
        return $output;
    }


    # Очистить состояния пулов сессии и кэш воркера
    static function cleanWorkerSession()
    {
        $streams = Stream::active()->get();
        DB::table('zen_worker_jobs')->truncate();
        ErrorLog::rotate();

        foreach ($streams as $stream) {
            $stream->clearState();
        }

        $cache = new Cabox('worker');
        $cache->purge();

        $log_path = storage_path('logs/worker.log');
        $log_archive_dir = storage_path('logs/worker');
        if(!file_exists($log_archive_dir)) mkdir($log_archive_dir, 0777);
        $log_date = date('d-m-Y__h-i-s');
        $log_archive_path = $log_archive_dir . "/worker_$log_date.zip";
        Zip::make($log_archive_path, $log_path);
        @unlink($log_path);
    }

    static function dumper($array)
    {
        $self = new self;
        return $self->htmlDump($array);
    }

    function htmlDump($array, $items_limit = 300000)
    {
        $dumper = new HtmlDumper;
        $cloner = new VarCloner();
        $cloner->setMaxItems($items_limit);
        return $dumper->dump($cloner->cloneVar($array), true);
    }


}
