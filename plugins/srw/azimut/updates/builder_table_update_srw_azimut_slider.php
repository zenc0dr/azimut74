<?php namespace Srw\Azimut\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSrwAzimutSlider extends Migration
{
    public function up()
    {
        Schema::table('srw_azimut_slider', function($table)
        {
            $table->string('link', 255)->nullable()->change();
        });
    }
    
    public function down()
    {
        Schema::table('srw_azimut_slider', function($table)
        {
            $table->string('link', 255)->nullable(false)->change();
        });
    }
}
