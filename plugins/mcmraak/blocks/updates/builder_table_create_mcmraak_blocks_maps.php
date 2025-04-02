<?php namespace Mcmraak\Blocks\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakBlocksMaps extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_blocks_maps', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->decimal('lat', 10, 6)->default(46.004441);
            $table->decimal('long', 10, 6)->default(51.540044);
            $table->string('w')->default('100%');
            $table->string('h')->default('300px');
            $table->integer('zoom')->default(17);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_blocks_maps');
    }
}