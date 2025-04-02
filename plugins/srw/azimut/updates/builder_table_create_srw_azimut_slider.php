<?php namespace Srw\Azimut\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateSrwAzimutSlider extends Migration
{
    public function up()
    {
        Schema::create('srw_azimut_slider', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('image', 255);
            $table->string('sltop', 255);
            $table->string('slmain', 255);
            $table->string('link', 255);
            $table->string('price', 255);
            $table->integer('order');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('srw_azimut_slider');
    }
}
