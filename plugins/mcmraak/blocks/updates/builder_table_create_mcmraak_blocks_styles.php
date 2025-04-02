<?php namespace Mcmraak\Blocks\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakBlocksStyles extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_blocks_styles', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('code');
            $table->text('html')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_blocks_styles');
    }
}