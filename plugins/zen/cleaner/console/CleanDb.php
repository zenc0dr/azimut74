<?php namespace Zen\Cleaner\Console;

use Illuminate\Console\Command;
use DB;

class CleanDb extends Command
{
    protected $name = 'cleaner:cleandb';
    protected $description = 'Clean system_files';

    public $removed_records;

    public function handle()
    {
        $this->removed_records = storage_path('removed_records.txt');
        DB::table('system_files')
            ->orderBy('id')
            ->chunk(100, function ($records) {
                foreach ($records as $record) {
                    $this->checkRecord($record);
                }
            });
    }

    public function checkRecord($record)
    {
        $spliced_file_name = $this->getPath($record->disk_name);
        $file_path = storage_path('app/uploads/public/'. $spliced_file_name);
        if (!file_exists($file_path)) {
            $record_json = json_encode($record, JSON_UNESCAPED_UNICODE);
            file_put_contents($this->removed_records, $record_json . "\n", FILE_APPEND);
            DB::table('system_files')
                ->where('id', $record->id)
                ->delete();
            echo "Запись о файле $record->disk_name удалена из базы\n";
        }
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
