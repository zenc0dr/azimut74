<?php namespace Srw\Azimut\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateSrwAzimutSlidercruises extends Migration
{
    public function up()
    {
        Schema::create('srw_azimut_slidercruises', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('image', 255);
            $table->string('sltop', 255)->nullable();
            $table->string('slmain', 255);
            $table->string('link', 255)->nullable();
            $table->string('price', 255)->nullable();
            $table->integer('order')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('srw_azimut_slidercruises');
    }
}
