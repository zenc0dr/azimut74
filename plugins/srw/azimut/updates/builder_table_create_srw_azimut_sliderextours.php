<?php namespace Srw\Azimut\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateSrwAzimutSliderextours extends Migration
{
    public function up()
    {
        Schema::create('srw_azimut_sliderextours', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('image');
            $table->string('sltop')->nullable();
            $table->string('slmain');
            $table->string('link')->nullable();
            $table->string('price')->nullable();
            $table->integer('order')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('srw_azimut_sliderextours');
    }
}
