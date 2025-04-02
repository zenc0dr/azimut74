<?php namespace Zen\GroupTours\Store;

use Zen\GroupTours\Models\FileBlock as FB;

class FileBlock extends Store
{
    public function get($data)
    {
        $fb = FB::find(1);

        if (!$fb) {
            return null;
        }

        $files = [];
        foreach ($fb->files as $file) {
            $files[] = [
                'name' => $file->title ?? $file->getFileName(),
                'filename' => $file->getFileName(),
                'desc' => $file->description,
                'path' => $file->path
            ];
        }

        return [
            'name' => $fb->name,
            'desc' => $fb->desc,
            'files' => $files
        ];
    }
}
