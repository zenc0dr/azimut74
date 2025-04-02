<?php namespace Srw\Azimut\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateSrwAzimutDirections extends Migration
{
    public function up()
    {
        Schema::create('srw_azimut_directions', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('category', 255);
            $table->string('name', 255);
            $table->string('desc', 255);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('srw_azimut_directions');
    }
}
