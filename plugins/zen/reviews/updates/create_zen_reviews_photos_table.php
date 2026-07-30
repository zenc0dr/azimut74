<?php namespace Zen\Reviews\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateZenReviewsPhotosTable extends Migration
{
    public function up()
    {
        Schema::create('zen_reviews_photos', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('system_file_id')->unsigned();
            $table->integer('review_id')->unsigned();
            $table->boolean('is_published')->default(false);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->unique('system_file_id');
            $table->index('review_id');
            $table->index('is_published');
        });
    }

    public function down()
    {
        Schema::dropIfExists('zen_reviews_photos');
    }
}
