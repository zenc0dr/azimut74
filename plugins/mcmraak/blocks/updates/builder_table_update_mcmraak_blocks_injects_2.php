<?php namespace Mcmraak\Blocks\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateMcmraakBlocksInjects2 extends Migration
{
    public function up()
    {
        Schema::table('mcmraak_blocks_injects', function($table)
        {
            $table->text('code')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('mcmraak_blocks_injects', function($table)
        {
            $table->dropColumn('code');
        });
    }
}
