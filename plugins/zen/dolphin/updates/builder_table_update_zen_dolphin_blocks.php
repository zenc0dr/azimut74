<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateZenDolphinBlocks extends Migration
{
    public function up()
    {
        Schema::table('zen_dolphin_blocks', function($table)
        {
            $table->smallInteger('active')->unsigned()->default(1);
        });
    }
    
    public function down()
    {
        Schema::table('zen_dolphin_blocks', function($table)
        {
            $table->dropColumn('active');
        });
    }
}
