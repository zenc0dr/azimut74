<?php namespace Mcmraak\Blocks\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakBlocksInjectsScopes extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_blocks_injects_scopes', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('inject_id');
            $table->integer('scope_id');
            $table->text('sequence')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_blocks_injects_scopes');
    }
}
