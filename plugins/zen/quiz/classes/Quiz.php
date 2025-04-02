<?php

namespace Zen\Quiz\Classes;

use Zen\Quiz\Traits\SingletonTrait;

class Quiz
{
    use SingletonTrait;

    public function api(string $path, string $method, ...$data)
    {
        $path = str_replace('.', '\\', $path);
        return app("Zen\Quiz\Classes\Api\\$path")->{$method}(...$data);
    }
}
