<?php namespace Zen\Cli\Classes;

use Zen\Cli\Models\Settings;

class Cli {
    public
        $nohup = false,
        $output = null,
        $output_errors = null,
        $php_path;

    function __construct()
    {
        $this->php_path = Settings::get('php_path');
    }

    function artisanExec($command)
    {
        $php_path = $this->php_path;
        $dir = base_path();
        $nohup = ($this->nohup)?'nohup ':'';
        $output = $this->output ?? '/dev/null';
        $output_errors = $this->output_errors ?? '/dev/null';
        $command = "$nohup$php_path $dir/artisan $command >$output 2>$output_errors &";

        #TODO(zenc0rd):DEBUG
        $time = now()->format('d.m.Y H:i:s');
        file_put_contents(
            storage_path('logs/amo_queries.log'),
            "$time: Команда на выполнение потока $command" . PHP_EOL,
            FILE_APPEND
        );


        exec($command);
        return $command;
    }

    # Запущен ли процесс парсинга (если $return_pid == true вернёт pid процесса)
    function processIsLaunched($entry)
    {
        $entry_preg = preg_quote($entry);
        exec("pgrep -f $entry_preg", $pids);
        if (count($pids) === 0) {
            return false;
        }
        return intval($pids[0]);
    }

    # Убить процесс
    function killProcess($entry)
    {
        $pid = $this->processIsLaunched($entry);
        if(!$pid) return;
        exec("kill -9 $pid");
    }

    # Функция преобразующая вывод процесса в html-лог
    function logRender($log_path)
    {
        $log = @file_get_contents($log_path);
        if(!$log) return;

        $log = explode("\n", $log);
        $save = false;
        foreach ($log as &$line) {
            if(strpos($line, "\r")!== false) {
                preg_match("/\r([^\r]+\r)$/", $line, $m);
                if($m) {
                    $line = $m[1];
                    $save = true;
                }
            }
        }
        $log = join("\n", $log);
        if($save) file_put_contents($log_path, $log);
        $log = str_replace("\n", '<br>', $log);
        return $log;
    }

}
