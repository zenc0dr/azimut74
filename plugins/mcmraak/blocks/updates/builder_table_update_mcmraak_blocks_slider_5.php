<?php namespace Mcmraak\Blocks\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateMcmraakBlocksSlider5 extends Migration
{
    public function up()
    {
        Schema::table('mcmraak_blocks_slider', function($table)
        {
            $table->integer('order')->default(0);
        });
    }
    
    public function down()
    {
        Schema::table('mcmraak_blocks_slider', function($table)
        {
            $table->dropColumn('order');
        });
    }
}
