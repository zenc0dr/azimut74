<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakRivercrsCabins extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_rivercrs_cabins', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('motorship_id');
            $table->string('category');
            $table->integer('places_main_count')->default(0);
            $table->integer('places_extra_count')->default(0);
            $table->integer('rooms_count')->default(0);
            $table->integer('bed_id')->default(0);
            $table->integer('comfort_id')->default(0);
            $table->integer('space')->default(0);
            $table->integer('order')->default(0);
            $table->text('desc')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_rivercrs_cabins');
    }
}