<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakRivercrsTechsPivot extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_rivercrs_techs_pivot', function($table)
        {
            $table->engine = 'InnoDB';
            #$table->increments('id');
            $table->integer('motorship_id');
            $table->integer('tech_id');
            $table->string('value');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_rivercrs_techs_pivot');
    }
}