<?php namespace Zen\Dolphin\Console;

use Illuminate\Console\Command;
use DB;

class RenderTodo extends Command
{
    protected $name = 'dolphin:rendertodo';
    protected $description = 'Render todo base';
    public $excluded_entity = [
        'Backend\Models\User'
    ];

    public $tasks = [];

    public function handle()
    {
        DB::table('system_files')
            ->orderBy('attachment_type')->orderBy('attachment_id')
            ->chunk(100, function ($records) {
                foreach ($records as $record) {
                    $this->handleRecord($record);
                }
            });

        $count = 0;

        foreach ($this->tasks as $task) {
            $count++;
            DB::table('zen_putpics_tasks')->insert([
                'attachment_type' => $task['attachment_type'],
                'attachment_id' => $task['attachment_id'],
                'photos_count' => $task['photos_count'],
            ]);
            echo "Добавлено $count  \r";
        }
    }

    public function handleRecord($record)
    {
        if (!$record->attachment_id) {
            return;
        }

        if (in_array($record->attachment_type, $this->excluded_entity)) {
            return;
        }

        $file_path = storage_path('app/uploads/public/'. $this->getPath($record->disk_name));
        if (!file_exists($file_path)) {
            if (class_exists($record->attachment_type)) {
                $model_item = app($record->attachment_type)->find($record->attachment_id);
                if ($model_item) {
                    $key = $record->attachment_type . ':' . $record->attachment_id;

                    if (!isset($this->tasks[$key]['attachment_id'])) {
                        $this->tasks[$key]['attachment_id'] = intval($record->attachment_id);
                    }

                    if (!isset($this->tasks[$key]['attachment_type'])) {
                        $this->tasks[$key]['attachment_type'] = $record->attachment_type;
                    }

                    if (!isset($this->tasks[$key]['photos_count'])) {
                        $this->tasks[$key]['photos_count'] = 1;
                    } else {
                        $this->tasks[$key]['photos_count'] ++;
                    }

                    echo "Обработано изображение: $record->disk_name \n";
                }
            }
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
