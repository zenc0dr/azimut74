<?php namespace Zen\Putpics\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use DB;

class Research extends Command
{
    protected $name = 'putpics:research';
    protected $description = 'Поиск и сравнение';
    public $founded_count = 0;
    public $log_path;

    public function handle()
    {
        $this->log_path = storage_path('found_files.txt');
        @unlink($this->log_path);

        $exemplars_path = '/home/zen/Downloads/Temp/azimut/upload_photos';
        $exemplars = $this->getFileList($exemplars_path);
        foreach ($exemplars as $exemplar) {
            //echo "Сканирую: $exemplar->name\n";
            $this->handleFile($exemplar);
        }
        echo "Найдено файлов: $this->founded_count\n";
    }

    public function getFileList($dir)
    {
        $fs = new Filesystem;
        $files = $fs->allFiles($dir, 1);
        $file_list = [];
        foreach ($files as $file) {
            $file_path = $file->getRelativePathname();
            $full_file_path = $dir.'/'.$file_path;
            $file_list[] = (object) [
                'name' => $file->getFilename(),
                'path' => $full_file_path,
                'size' => filesize($full_file_path),
            ];
        }
        return $file_list;
    }

    public function handleFile($exemplar)
    {
        $db_record = DB::table('system_files')
            ->where('file_name', $exemplar->name)
            ->where('file_size', $exemplar->size)
            ->first();

        if (!$db_record) {
            return;
        }

        $spliced_file_name = $this->getPath($db_record->disk_name);
        $file_path = storage_path('app/uploads/public/'. $spliced_file_name);
        if (file_exists($file_path)) {
            return;
        }

        $this->founded_count ++;
        $raw = "$exemplar->path|$db_record->attachment_type\n";
        echo $raw;
        file_put_contents($this->log_path, $raw, FILE_APPEND);

        $restore_file_path = '/home/zen/Downloads/Temp/azimut/restore/public/' . $this->getPath($db_record->disk_name);
        if (!file_exists(dirname($restore_file_path))) {
            mkdir(dirname($restore_file_path), 0777, true);
        }

        copy($exemplar->path, $restore_file_path);
    }

    public function checkInStorage($record)
    {
        $spliced_file_name = $this->getPath($record->disk_name);
        $file_path = storage_path('app/uploads/public/'. $spliced_file_name);
    }

    public function getPath($filename)
    {
        return $this->getPartitionDirectory($filename) . $filename;
    }

    public function getPartitionDirectory($filename)
    {
        return implode('/', array_slice(str_split($filename, 3), 0, 3)) . '/';
    }
}
