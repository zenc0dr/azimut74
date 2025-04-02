<?php namespace Mcmraak\Blocks\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakBlocksScopes extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_blocks_scopes', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->nullable();
            $table->text('code')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_blocks_scopes');
    }
}
