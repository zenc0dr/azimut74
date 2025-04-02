<?php namespace Mcmraak\Blocks\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakBlocksPosts extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_blocks_posts', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->smallInteger('active')->unsigned()->default(1);
            $table->smallInteger('onlyimage')->unsigned()->default(0);
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->string('text')->nullable();
            $table->dateTime('time_start')->nullable();
            $table->dateTime('time_end')->nullable();
            $table->integer('sort_order')->default(0);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_blocks_posts');
    }
}