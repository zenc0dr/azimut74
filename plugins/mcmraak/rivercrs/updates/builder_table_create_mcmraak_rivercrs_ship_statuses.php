<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakRivercrsShipStatuses extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_rivercrs_ship_statuses', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();
            $table->text('desc')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_rivercrs_ship_statuses');
    }
}