<?php namespace Zen\Reviews\Updates;

use DB;
use October\Rain\Database\Updates\Migration;
use Zen\Reviews\Models\Review;

class BackfillZenReviewsPhotos extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');

        DB::table('system_files')
            ->where('attachment_type', Review::class)
            ->where('field', 'photos')
            ->orderBy('id')
            ->chunk(200, function ($files) use ($now) {
                $rows = [];

                foreach ($files as $file) {
                    $exists = DB::table('zen_reviews_photos')
                        ->where('system_file_id', (int) $file->id)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    $rows[] = [
                        'system_file_id' => (int) $file->id,
                        'review_id' => (int) $file->attachment_id,
                        'is_published' => 1,
                        'created_at' => $file->created_at ?: $now,
                        'updated_at' => $file->updated_at ?: $now,
                    ];
                }

                if ($rows !== []) {
                    DB::table('zen_reviews_photos')->insert($rows);
                }
            });
    }

    public function down()
    {
        DB::table('zen_reviews_photos')->truncate();
    }
}
