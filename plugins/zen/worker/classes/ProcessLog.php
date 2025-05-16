<?php namespace Zen\Worker\Classes;

class ProcessLog
{
    public static function add($message)
    {
        if (request('debug')) {
            return;
        }

        $time = date('d.m.Y H:i:s');
        $message = "$time >> $message\n";
        echo $message;

        $log_path = storage_path('logs/worker.log');
        file_put_contents($log_path, $message, FILE_APPEND);
    }
}
