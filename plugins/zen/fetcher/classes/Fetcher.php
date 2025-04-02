<?php

namespace Zen\Fetcher\Classes;

use Zen\Fetcher\Traits\SingletonTrait;
use Carbon\Carbon;

class Fetcher
{
    use SingletonTrait;

    public function api(string $path, string $method, ...$data)
    {
        $path = str_replace('.', '\\', $path);
        return app("Zen\Fetcher\Classes\Api\\$path")->{$method}(...$data);
    }

    public function console(string $path, $data = null)
    {
        $method = \Str::afterLast($path, '.');
        $path = preg_replace('/\.[^.]+$/','', $path);
        $path = str_replace('.', '\\', $path);
        app("Zen\Fetcher\Console\\$path")->{$method}($data);
    }

    function carbon($date, $format = null): \Carbon\Carbon
    {
        if ($format) {
            return \Carbon\Carbon::createFromFormat($format, $date);
        }
        return \Carbon\Carbon::parse($date);
    }

    public function stream(string $stream_code): \Zen\Fetcher\Classes\Services\StreamManager
    {
        return new \Zen\Fetcher\Classes\Services\StreamManager($stream_code);
    }

    public function pool(string $pool_code): \Zen\Fetcher\Classes\Services\PoolManager
    {
        return new \Zen\Fetcher\Classes\Services\PoolManager($pool_code);
    }

    public function settings(string $key)
    {
        return \Zen\Fetcher\Models\Settings::get($key);
    }

    public function mysql(): \Zen\Fetcher\Classes\Services\Database\MySqlManager
    {
        return \Zen\Fetcher\Classes\Services\Database\MySqlManager::getInstance();
    }

    public function models(): \Zen\Fetcher\Classes\Services\Database\Models
    {
        return \Zen\Fetcher\Classes\Services\Database\Models::getInstance();
    }

    public function files(): \Zen\Fetcher\Classes\Services\Filesystem\Files
    {
        return \Zen\Fetcher\Classes\Services\Filesystem\Files::getInstance();
    }

    public function db(string $table, string $connection = 'mysql')
    {
        return \DB::connection($connection)->table($table);
    }

    public function json(string $path): Services\Json
    {
        return new \Zen\Fetcher\Classes\Services\Json($path);
    }

    public function fromJson($string, $assoc = true): ?array
    {
        if (empty($string)) {
            return null;
        }
        return json_decode($string, $assoc);
    }

    public function toJson($arr, $pretty_print = false, $no_slashes = false): ?string
    {
        if (!$arr) {
            return null;
        }
        if (!is_array($arr)) {
            return null;
        }

        $options = JSON_UNESCAPED_UNICODE;
        if ($pretty_print) {
            $options |= JSON_PRETTY_PRINT;
        }
        if ($no_slashes) {
            $options |= JSON_UNESCAPED_SLASHES;
        }

        return json_encode($arr, $options);
    }

    public function sources(): Sources\SourceManager
    {
        return \Zen\Fetcher\Classes\Sources\SourceManager::getInstance();
    }

    # Отправить задание на выполнение
    public function push(string $path, $data = null)
    {
        (new \Zen\Fetcher\Classes\Services\QueueManager)->push($path, $data);
    }
}
