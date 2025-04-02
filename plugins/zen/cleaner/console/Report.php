<?php namespace Zen\Cleaner\Console;

use Illuminate\Console\Command;
use DB;

class Report extends Command
{
    protected $name = 'cleaner:report';
    protected $description = 'Render repott about missing files';

    public $log_file_path, $all_count, $progress, $missing_count;

    public function handle()
    {
        $this->log_file_path = storage_path('missing_files.txt');
        @unlink($this->log_file_path);

        $this->all_count = DB::table('system_files')->count();

        DB::table('system_files')
            ->orderBy('id')
            ->chunk(100, function ($records) {
                foreach ($records as $record) {
                    $this->progress++;
                    $missing = $this->checkRecord($record);
                    if ($missing) {
                        $this->missing_count++;
                    }
                }
                echo "Обработано [$this->progress из $this->all_count] Не найдено: $this->missing_count   \r";
            });
        echo "\nОтчёт по адресу: $this->log_file_path\n";
    }

    public function checkRecord($record)
    {
        $spliced_file_name = $this->getPath($record->disk_name);
        $file_path = storage_path('app/uploads/public/'. $spliced_file_name);
        if (!file_exists($file_path)) {
            if (class_exists($record->attachment_type)) {
                $model_item = app($record->attachment_type)->find($record->attachment_id);
                if ($model_item) {
                    $raw = "$record->attachment_type|$record->file_name|$record->attachment_id\n";
                    file_put_contents($this->log_file_path, $raw, FILE_APPEND);
                    return true;
                }
            }
        }
        return false;
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
