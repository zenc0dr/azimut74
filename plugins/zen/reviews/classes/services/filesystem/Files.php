<?php

namespace Zen\Reviews\Classes\Services\Filesystem;

use Zen\Reviews\Traits\SingletonTrait;

class Files
{
    use SingletonTrait;

    public function chekFileDir(string $path): string
    {
        $directory_path = dirname($path);
        if (!file_exists($directory_path)) {
            mkdir($directory_path, 0777, true);
        }
        return $path;
    }

    public function arrayToFile(array $array, string $file_name = null): void
    {
        file_put_contents(
            $this->chekFileDir($file_name ?? storage_path('reviews_dump.json')),
            reviews()->toJson($array, true)
        );
    }

    public function arrayFromFile(string $file_name = null): array
    {
        return reviews()->fromJson(
            file_get_contents($file_name ?? storage_path('reviews_dump.json'))
        );
    }
}
