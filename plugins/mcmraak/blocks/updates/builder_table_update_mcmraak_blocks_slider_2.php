<?php namespace Mcmraak\Blocks\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateMcmraakBlocksSlider2 extends Migration
{
    public function up()
    {
        Schema::table('mcmraak_blocks_slider', function($table)
        {
            $table->text('slides')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('mcmraak_blocks_slider', function($table)
        {
            $table->dropColumn('slides');
        });
    }
}
