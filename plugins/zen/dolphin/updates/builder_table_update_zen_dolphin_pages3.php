<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateZenDolphinPages3 extends Migration
{
    public function up()
    {
        Schema::table('zen_dolphin_pages', function($table)
        {
            $table->integer('group_sort_order')->default(100);
        });
        \DB::unprepared('update `zen_dolphin_pages` set `group_sort_order` = id * 100');
    }

    public function down()
    {
        Schema::table('zen_dolphin_pages', function($table)
        {
            $table->dropColumn('group_sort_order');
        });
    }
}