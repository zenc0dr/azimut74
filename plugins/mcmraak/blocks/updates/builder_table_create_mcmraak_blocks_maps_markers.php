<?php namespace Mcmraak\Blocks\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakBlocksMapsMarkers extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_blocks_maps_markers', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('map_id');
            $table->integer('marker_id');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_blocks_maps_markers');
    }
}