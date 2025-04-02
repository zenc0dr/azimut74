<?php namespace Mcmraak\Blocks\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateMcmraakBlocksSlider extends Migration
{
    public function up()
    {
        Schema::table('mcmraak_blocks_slider', function($table)
        {
            $table->string('slug', 255)->nullable();
            $table->smallInteger('active')->unsigned()->default(1);
        });
    }
    
    public function down()
    {
        Schema::table('mcmraak_blocks_slider', function($table)
        {
            $table->dropColumn('slug');
            $table->dropColumn('active');
        });
    }
}
