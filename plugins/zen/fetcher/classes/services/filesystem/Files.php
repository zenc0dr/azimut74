<?php

namespace Zen\Fetcher\Classes\Services\Filesystem;

use Zen\Fetcher\Traits\SingletonTrait;

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

    public function deleteDir(string $folder): void
    {
        if (is_dir($folder)) {
            $handle = opendir($folder);
            while ($file = readdir($handle)) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                if (is_file($file)) {
                    unlink("$folder/$file");
                } else {
                    $this->deleteDir("$folder/$file");
                }
            }
            closedir($handle);
            rmdir($folder);
        } else {
            unlink($folder);
        }
    }

    public function arrayToFile(array $array, string $file_name = null): void
    {
        file_put_contents(
            $this->chekFileDir($file_name ?? storage_path('fetcher_dump.json')),
            fetcher()->toJson($array, true)
        );
    }

    public function arrayFromFile(string $file_name = null)
    {
        $file_path = $file_name ?? storage_path('fetcher_dump.json');
        if (!file_exists($file_path)) {
            return null;
        }
        return fetcher()->fromJson(
            file_get_contents($file_path)
        );
    }
}
