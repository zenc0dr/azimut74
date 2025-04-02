<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakRivercrsIncabin extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_rivercrs_incabin', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->text('name');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_rivercrs_incabin');
    }
}
