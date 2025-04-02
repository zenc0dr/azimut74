<?php

namespace Zen\Fetcher\Classes\Services\Database;

use Zen\Fetcher\Traits\SingletonTrait;
use Zen\Fetcher\Models\Pool;
use Illuminate\Database\Eloquent\Builder;

class Models
{
    use SingletonTrait;

    public function pool(): Builder
    {
        return Pool::query();
    }
}
