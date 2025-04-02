<?php namespace Srw\Azimut\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSrwAzimutSlider2 extends Migration
{
    public function up()
    {
        Schema::table('srw_azimut_slider', function($table)
        {
            $table->string('sltop', 255)->nullable()->change();
            $table->string('price', 255)->nullable()->change();
            $table->integer('order')->nullable()->change();
        });
    }
    
    public function down()
    {
        Schema::table('srw_azimut_slider', function($table)
        {
            $table->string('sltop', 255)->nullable(false)->change();
            $table->string('price', 255)->nullable(false)->change();
            $table->integer('order')->nullable(false)->change();
        });
    }
}
