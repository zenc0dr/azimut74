<?php

namespace Zen\Fetcher\Classes\Sources;

use Zen\Fetcher\Traits\SingletonTrait;

/**
 * SourceManager - Предоставляет chaining-splitter для вызова источников
 * ex: reviews()->sources()->AmoCrmSource()
 */

class SourceManager
{
    use SingletonTrait;

    public function InfoflotSource($pool)
    {
        return new \Zen\Fetcher\Classes\Sources\Crs\InfoflotSource($pool);
    }
}
