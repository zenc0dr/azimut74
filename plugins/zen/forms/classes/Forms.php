<?php

namespace Zen\Forms\Classes;

use Zen\Forms\Traits\SingletonTrait;

class Forms
{
    use SingletonTrait;

    public function api(string $path, string $method, ...$data)
    {
        $path = str_replace('.', '\\', $path);
        return app("Zen\Forms\Classes\Api\\$path")->{$method}(...$data);
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

    # Обработка ответа
    public function response($response = null): ?string
    {
        $dd = boolval(request('dd', false));
        if (is_string($response)) {
            return $response;
        } else {
            if ($response === null) {
                $response = [
                    'success' => true
                ];
            } else {
                if (!isset($response['success'])) {
                    $response['success'] = true;
                }
            }
        }
        if ($dd) {
            dd($response);
        }
        return $this->toJson($response);
    }
}
