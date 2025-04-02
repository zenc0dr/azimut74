<?php namespace Mcmraak\Blocks\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateMcmraakBlocksInjects extends Migration
{
    public function up()
    {
        Schema::table('mcmraak_blocks_injects', function($table)
        {
            $table->text('link')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('mcmraak_blocks_injects', function($table)
        {
            $table->dropColumn('link');
        });
    }
}