<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakRivercrsIncabinPivot extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_rivercrs_incabin_pivot', function($table)
        {
            $table->engine = 'InnoDB';
            #$table->increments('id');
            $table->integer('cabin_id');
            $table->integer('incabin_id');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_rivercrs_incabin_pivot');
    }
}
