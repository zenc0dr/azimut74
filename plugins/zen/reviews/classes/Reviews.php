<?php

namespace Zen\Reviews\Classes;

use Zen\Reviews\Traits\SingletonTrait;

class Reviews
{
    use SingletonTrait;

    public function api(string $path, string $method, ...$data)
    {
        $path = str_replace('.', '\\', $path);
        return app("Zen\Reviews\Classes\Api\\$path")->{$method}(...$data);
    }

    public function files(): \Zen\Reviews\Classes\Services\Filesystem\Files
    {
        return \Zen\Reviews\Classes\Services\Filesystem\Files::getInstance();
    }

    public function image(array $image_arr): \Zen\Reviews\Classes\Services\Images\Image
    {
        return new \Zen\Reviews\Classes\Services\Images\Image($image_arr);
    }

    public function db(string $table): \Illuminate\Database\Query\Builder
    {
        return \DB::table($table);
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
}
