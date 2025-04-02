<?php namespace Zen\GroupTours\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateZenGrouptoursTours extends Migration
{
    public function up()
    {
        Schema::table('zen_grouptours_tours', function($table)
        {
            $table->string('youtube_link')->nullable();
            $table->text('media_images')->nullable();
            $table->text('important_info')->nullable();
            $table->text('announcement')->nullable();
            $table->text('tour_program')->nullable();
            $table->text('conditions')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('zen_grouptours_tours', function($table)
        {
            $table->dropColumn('youtube_link');
            $table->dropColumn('media_images');
            $table->dropColumn('important_info');
            $table->dropColumn('announcement');
            $table->dropColumn('tour_program');
            $table->dropColumn('conditions');
        });
    }
}
