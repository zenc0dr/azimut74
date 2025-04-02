<?php

namespace Zen\Master\Classes\Services;

use Zen\Master\Traits\SingletonTrait;
use Zen\Master\Models\LogModel;

class Logger
{
    use SingletonTrait;

    public function add(string $name, array $data)
    {
        LogModel::create([
            'event_name' => $name,
            'data' => master()->toJson($data, true),
            'ip' => request()->ip()
        ]);
    }
}
