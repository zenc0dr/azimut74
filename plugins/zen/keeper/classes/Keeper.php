<?php namespace Zen\Keeper\Classes;

use Illuminate\Filesystem\Filesystem;
use October\Rain\Filesystem\Zip;
use DB;

class Keeper extends Core
{
    public function createBackup()
    {
        $temp_path = temp_path('keeper_backup');
        $plugins_path = base_path('plugins');
        $themes_path = base_path('themes');
        $fileSystem = new Filesystem;

        if (file_exists($temp_path)) {
            $fileSystem->deleteDirectory($temp_path, false);
        }

        mkdir($temp_path, 0777);
        $gzip_temp = $temp_path . '/temp';

        if (!file_exists($gzip_temp)) {
            mkdir($gzip_temp, 0777);
        }

        $fileSystem->copyDirectory($plugins_path, $temp_path . '/temp/plugins');
        $fileSystem->copyDirectory($themes_path, $temp_path . '/temp/themes');

        # Копировать файлы из корня
        $base_files = $fileSystem->files(base_path(), 1);

        foreach ($base_files as $file) {
            $ext = $file->getExtension();
            if (in_array($ext, ['zip', 'log'])) {
                continue;
            }
            $path = $file->getPathname();
            $target = $temp_path . '/temp/' . $file->getBasename();
            $fileSystem->copy($path, $target);
        }

        $this->createDbDump($temp_path . '/temp/dump.sql.gz');

        Zip::make("$temp_path/backup.zip", function ($zip) use ($temp_path) {
            $zip->add("$temp_path/temp/*", ['includeHidden' => true]);
            $zip->remove([
                '*.zip'
            ]);
        });

        $fileSystem->deleteDirectory($temp_path . '/temp', false);
    }

    public function createDbDump($filename)
    {
        $dbname = config('database.connections.mysql.database');
        $dbuser = config('database.connections.mysql.username');
        $dbpass = config('database.connections.mysql.password');

        $query = "mysqldump -u$dbuser -p$dbpass $dbname 2>/dev/null | gzip > $filename";
        exec($query);

        if (!file_exists($filename)) {
            return false;
        }

        return true;
    }
}
