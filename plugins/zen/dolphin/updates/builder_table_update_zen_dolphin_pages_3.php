<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateZenDolphinPages3 extends Migration
{
    public function up()
    {
        Schema::table('zen_dolphin_pages', function($table)
        {
            $table->integer('feature_id')->default(0);
        });
    }
    
    public function down()
    {
        Schema::table('zen_dolphin_pages', function($table)
        {
            $table->dropColumn('feature_id');
        });
    }
}
