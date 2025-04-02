<?php namespace Zen\Putpics\Console;

use Illuminate\Console\Command;
use Zen\Putpics\Models\Task;
use DB;

class Actualize extends Command
{
    protected $name = 'putpics:actualize';
    protected $description = 'Реактулизация изображений';

    public function handle()
    {
        $closed_tasks = 0;
        $tasks = Task::where('success', 0)->get();
        foreach ($tasks as $task) {
            $restore_count = 0;
            $db_files = DB::table('system_files')
                ->where('attachment_type', $task->attachment_type)
                ->where('attachment_id', $task->attachment_id)
                ->get();
            foreach ($db_files as $db_file) {
                $spliced_file_name = $this->getPath($db_file->disk_name);
                $file_path = storage_path('app/uploads/public/'. $spliced_file_name);
                if (file_exists($file_path)) {
                    $restore_count++;
                }
            }

            if ($restore_count === intval($task->photos_count)) {
                $task->user_id = 1;
                $task->success = 1;
                $task->save();
                echo "Задача $task->id закрыта\n";
                $closed_tasks++;
            }
        }

        echo "Закрыто задач $closed_tasks\n";
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
