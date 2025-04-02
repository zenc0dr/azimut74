<?php namespace Mcmraak\Blocks\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakBlocksGalleries extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_blocks_galleries', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->nullable();
            $table->integer('style_id')->default(0);
            $table->string('code');
            $table->smallInteger('active')->default(1);
            $table->integer('sort_order')->default(0);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_blocks_galleries');
    }
}