<?php namespace Zen\Putpics\Api;

use Input;
use Log;
use DB;
use Zen\Dolphin\Models\Hotel;
use Zen\Putpics\Models\Task;
use System\Models\File;
use Exception;
use Zen\Putpics\Classes\Helpers;

class Saver
{
    public function savePackage()
    {
        $package = Input::get('putpics_db');

        $hotel_id = $package['hotel_id'];
        $urls = $package['urls'];
        $hotel = Hotel::find($hotel_id);

        if (!$hotel) {
            return json_encode([
                'success' => false,
                'message' => 'Hotel not exists.'
            ]);
        }

        foreach ($urls as $url) {
            try {
                $file = (new File)->fromUrl($url);
                $file->is_public = true;
                $file->save();
                $hotel->images()->add($file);
            } catch (Exception $exception) {
                Log::debug([
                    'message' => $exception->getMessage()
                ]);
            }
        }

        $missing_photos = $this->checkAndCloseTask($hotel_id);

        return json_encode([
            'success' => true,
            'message' => $missing_photos === 0
                ? "Задача отеля #$hotel->id закрыта"
                : "Задача НЕ закрыта, не хватает $missing_photos фото"
        ]);
    }

    public function checkAndCloseTask($hotel_id)
    {
        $files = DB::table('system_files')
            ->where('attachment_type', 'Zen\Dolphin\Models\Hotel')
            ->where('attachment_id', $hotel_id)
            ->get();

        $exists_files_count = 0;

        foreach ($files as $file) {
            $spliced_file_name = Helpers::getSlicedPath($file->disk_name);
            $file_path = storage_path('app/uploads/public/'. $spliced_file_name);
            if (file_exists($file_path)) {
                $exists_files_count++;
            }
        }

        if ($exists_files_count > 5) {
            $task = Task::where('attachment_type', 'Zen\Dolphin\Models\Hotel')
                ->where('attachment_id', $hotel_id)
                ->first();
            $task->success = 1;
            if (!$task->user_id) {
                $task->user_id = 1;
            }
            $task->save();
            return 0;
        }

        return 5 - $exists_files_count;
    }
}
