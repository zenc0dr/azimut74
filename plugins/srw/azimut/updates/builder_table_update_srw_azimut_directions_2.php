<?php namespace Srw\Azimut\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSrwAzimutDirections2 extends Migration
{
    public function up()
    {
        Schema::table('srw_azimut_directions', function($table)
        {
            $table->integer('order');
        });
    }
    
    public function down()
    {
        Schema::table('srw_azimut_directions', function($table)
        {
            $table->dropColumn('order');
        });
    }
}
