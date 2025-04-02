<?php namespace Srw\Azimut\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSrwAzimutDirections3 extends Migration
{
    public function up()
    {
        Schema::table('srw_azimut_directions', function($table)
        {
            $table->string('category', 255)->nullable()->change();
            $table->string('desc', 255)->nullable()->change();
            $table->integer('order')->nullable()->change();
        });
    }
    
    public function down()
    {
        Schema::table('srw_azimut_directions', function($table)
        {
            $table->string('category', 255)->nullable(false)->change();
            $table->string('desc', 255)->nullable(false)->change();
            $table->integer('order')->nullable(false)->change();
        });
    }
}
