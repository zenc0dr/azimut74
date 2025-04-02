<?php namespace Zen\GroupTours\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenGrouptoursTours extends Migration
{
    public function up()
    {
        Schema::create('zen_grouptours_tours', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();
            $table->integer('days')->default(1);
            $table->text('waybill')->nullable();
            $table->integer('price')->default(0);
            $table->smallInteger('active')->default(1);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_grouptours_tours');
    }
}
