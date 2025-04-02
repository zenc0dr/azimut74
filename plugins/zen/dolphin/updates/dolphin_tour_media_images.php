<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class DolphinTourMediaImages extends Migration
{
    public function up()
    {
        Schema::table('zen_dolphin_tours', function($table)
        {
            $table->text('media_images')->nullable();
        });
    }

    public function down()
    {
        Schema::table('zen_dolphin_tours', function($table)
        {
            $table->dropColumn('media_images');
        });
    }
}