<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakRivercrsTechs extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_rivercrs_techs', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_rivercrs_techs');
    }
}