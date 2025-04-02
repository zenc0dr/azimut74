<?php namespace Srw\Azimut\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateSrwAzimutCallme extends Migration
{
    public function up()
    {
        Schema::create('srw_azimut_callme', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name', 255)->nullable();
            $table->string('phone', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->text('message')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('srw_azimut_callme');
    }
}