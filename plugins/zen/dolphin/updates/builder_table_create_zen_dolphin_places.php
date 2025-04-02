<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenDolphinPlaces extends Migration
{
    public function up()
    {
        Schema::create('zen_dolphin_places', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->text('name')->nullable();
            $table->text('geo_code');
            $table->text('geo_name')->nullable();
            $table->integer('sort_order')->unsigned()->default(0);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_dolphin_places');
    }
}