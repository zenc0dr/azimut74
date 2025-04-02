<?php namespace Mcmraak\Blocks\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakBlocksMarkers extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_blocks_markers', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->nullable();
            $table->decimal('lat', 10, 6)->default(46.004441);
            $table->decimal('long', 10, 6)->default(51.540044);
            $table->string('title')->nullable();
            $table->text('text')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_blocks_markers');
    }
}