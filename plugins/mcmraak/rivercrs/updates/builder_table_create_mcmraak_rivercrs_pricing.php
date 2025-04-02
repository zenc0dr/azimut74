<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakRivercrsPricing extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_rivercrs_pricing', function($table)
        {
            $table->engine = 'InnoDB';
            # $table->increments('id');
            $table->integer('checkin_id');
            $table->integer('cabin_id');
            $table->integer('price_a')->nullable();
            $table->integer('price_b')->nullable();
            $table->text('desc')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_rivercrs_pricing');
    }
}