<?php namespace Zen\Quiz\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenQuizCities extends Migration
{
    public function up()
    {
        Schema::create('zen_quiz_cities', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name');
            $table->boolean('active')->nullable();
            $table->integer('sort_order')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_quiz_cities');
    }
}
