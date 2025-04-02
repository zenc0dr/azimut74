<?php namespace Mcmraak\Blocks\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakBlocksInjects extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_blocks_injects', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->nullable();
            $table->integer('template_id')->default(0);
            $table->string('image')->nullable();
            $table->text('html')->nullable();
            $table->smallInteger('active')->default(1);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_blocks_injects');
    }
}