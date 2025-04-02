<?php namespace Mcmraak\Blocks\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakBlocksSlider extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_blocks_slider', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name', 255);
            $table->string('image', 255);
            $table->string('sltop', 255)->nullable();
            $table->string('slmain', 255)->nullable();
            $table->string('link', 255)->nullable();
            $table->string('price', 255)->nullable();
            $table->integer('order')->nullable()->default(100);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_blocks_slider');
    }
}
