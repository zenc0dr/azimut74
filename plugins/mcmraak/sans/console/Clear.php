<?php namespace Mcmraak\Sans\Console;

use Illuminate\Console\Command;

use Illuminate\Filesystem\Filesystem;

class Clear extends Command
{

    protected $name = 'sans:clear';
    protected $description = 'Очистка временных файлов';

    public function handle()
    {
        $now = time();
        $lifetime = 259200; // 3 дня
        $dir_path = storage_path('sans_cache/queries');
        foreach (self::getFileList($dir_path) as $file) {
            if ($now - $file['time'] > $lifetime) {
                unlink($file['path']);
                echo "Очистка: {$file['path']}\n";
            }
        }
    }

    static function getFileList($dir): \Generator
    {
        $fs = new Filesystem;
        $files = $fs->allFiles($dir, 1);
        foreach ($files as $file)
        {
            yield [
                'path' => $file->getRealPath(),
                'time' => $file->getMTime(),
            ];
        }
    }
}
