<?php

namespace Zen\Master\Classes;

use Zen\Master\Traits\SingletonTrait;
use Zen\Master\Models\Settings;
use Carbon\Carbon;

class Master
{
    use SingletonTrait;

    private array $memory = [];

    public function memorySet(string $key, $value)
    {
        $this->memory[$key] = $value;
    }

    public function memoryGet(string $key)
    {
        return $this->memory[$key] ?? null;
    }

    public function settings(string $key)
    {
        return Settings::get($key);
    }

    public function blackList()
    {
        return collect($this->settings('phones'))
            ->where('active', 1)
            ->pluck('phone')
            ->toArray();
    }

    public function fromJson($string, $assoc = true): ?array
    {
        if (is_null($string)) {
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

    public function files(): \Zen\Master\Classes\Services\Files
    {
        return \Zen\Master\Classes\Services\Files::getInstance();
    }

    public function log(string $name = null, array $data = null): ?\Zen\Master\Classes\Services\Logger
    {
        if ($name) {
            \Zen\Master\Classes\Services\Logger::getInstance()->add($name, $data);
            return null;
        }
        return \Zen\Master\Classes\Services\Logger::getInstance();
    }

    public function carbon($date = null, $format = null): Carbon
    {
        return $format
            ? \Carbon\Carbon::createFromFormat($format, $date)
            : \Carbon\Carbon::parse($date ?? now());
    }

    /**
     * Не блокирующий метод для получения значений потребляемых ресурсов в момент вызова
     * @return int[]
     */
    public function getSystemUsage(): array
    {
        $duration = microtime(true) - LARAVEL_START;
        return [
            'cpu_ms' => (int) ($duration * 1000),
            'mem_mb' => (int) (memory_get_peak_usage(true) / 1024 / 1024)
        ];
    }

    /**
     * Операции со строками
     * @return Services\Strings
     */
    public function strings(): \Zen\Master\Classes\Services\Strings
    {
        return \Zen\Master\Classes\Services\Strings::getInstance();
    }

    /**
     * Уведомление в телеграм бот
     * @return Services\Telegram
     */
    public function telegram(): \Zen\Master\Classes\Services\Telegram
    {
        return \Zen\Master\Classes\Services\Telegram::getInstance();
    }
}
