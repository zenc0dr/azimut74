<?php namespace Mcmraak\Sans\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakSansMeals extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_sans_meals', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('code');
            $table->string('name')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_sans_meals');
    }
}