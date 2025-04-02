<?php

namespace Zen\Fetcher\Classes\Services;

use Queue;

class QueueManager
{
    # Добавить задание в очередь ex: очередь.Zen.Crs.Classes.Class.method:1
    public function push(string $path, $data = null)
    {
        $path = explode('.', $path);
        $queue = array_shift($path);
        $method = array_pop($path);
        $class = join('\\', $path);
        Queue::push('Zen\Fetcher\Classes\Services\QueueManager@perform', [
            'class' => $class,
            'method' => $method,
            'data' => $data
        ], $queue);
    }

    # Выполнить задание
    public function perform($job, $data)
    {
        $class = $data['class'];
        $method = $data['method'];
        $data = $data['data'];
        app($class)->{$method}($data);
        $job->delete();
    }
}
