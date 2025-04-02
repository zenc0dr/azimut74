<?php namespace Zen\Putpics\Console;

use Illuminate\Console\Command;
use Zen\Putpics\Models\Task;
use DB;

class HasImages extends Command
{
    protected $name = 'putpics:has-images';
    protected $description = 'Fill pics';

    public function handle()
    {
        $tasks = Task::get();
        $count = 0;

        foreach ($tasks as $task) {
            if ($task->attachment_type === 'Zen\Dolphin\Models\Hotel') {
                $hotel = DB::table('zen_dolphin_hotels')
                    ->where('id', $task->attachment_id)
                    ->first();

                echo "$hotel->id:$hotel->gallery\n";

                if ($hotel->gallery !== null) {
                    $task->has_images = 1;
                    $task->save();
                    echo "Помечен отель $hotel->name как has_images\n";
                    $count++;
                }
            }
        }

        echo "Помечено $count\n";
    }
}
