<?php

namespace Zen\Reviews\Classes\Services\Images;

use Exception;

class Image
{
    private string $original__file_name;
    private string $extension;
    private string $base64;
    private string $file_name;
    private string $file_path;
    private string $file_url;

    public function __construct(array $image_array) # [file_name, base64]
    {
        $this->original__file_name = $image_array['file_name'];
        $this->base64Decompose($image_array['base64']);
    }

    private function base64Decompose($base64)
    {
        $base64_prepared = null;
        $ext = null;

        if (str_starts_with($base64, 'data:image/jpeg;base64,')) {
            $base64_prepared = str_replace('data:image/jpeg;base64,', '', $base64);
            $ext = 'jpg';
        }

        if (str_starts_with($base64, 'data:image/png;base64,')) {
            $base64_prepared = str_replace('data:image/png;base64,', '', $base64);
            $ext = 'png';
        }

        if (!$ext) {
            throw new Exception('Corrupted extension');
        }

        $this->base64 = $base64_prepared;
        $this->extension = $ext;
    }

    public function getFileName(): string
    {
        return $this->original__file_name;
    }

    public function getDiskName(): string
    {
        return $this->file_name;
    }

    public function getUrl(): string
    {
        return '/' . $this->file_url;
    }

    public function getRelativePath(): string
    {
        return $this->file_url;
    }

    public function getFullPath(): string
    {
        return $this->file_path;
    }

    public function store(): self
    {
        $file_name = md5($this->base64) . '.' . $this->extension;
        $file_data = base64_decode($this->base64);
        $file_url = self::getPublicPath($file_name);
        $file_path = base_path($file_url);
        reviews()->files()->chekFileDir($file_path);
        file_put_contents($file_path, $file_data);
        $this->file_name = $file_name;
        $this->file_url = $file_url;
        $this->file_path = $file_path;
        return $this;
    }

    /* Вернуть относительный путь до файла */
    public static function getPublicPath($filename): string
    {
        return 'storage/app/uploads/public/'
            . implode(
                '/',
                array_slice(
                    str_split(
                        $filename,
                        3
                    ),
                    0,
                    3
                )
            )
            . '/'
            . $filename;
    }
}
