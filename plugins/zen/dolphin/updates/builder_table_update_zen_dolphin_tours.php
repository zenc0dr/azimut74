<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateZenDolphinTours extends Migration
{
    public function up()
    {
        Schema::table('zen_dolphin_tours', function($table)
        {
            $table->smallInteger('allocation')->default(0);
        });
    }
    
    public function down()
    {
        Schema::table('zen_dolphin_tours', function($table)
        {
            $table->dropColumn('allocation');
        });
    }
}