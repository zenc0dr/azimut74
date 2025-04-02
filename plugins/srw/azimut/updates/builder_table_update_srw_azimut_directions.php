<?php namespace Srw\Azimut\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSrwAzimutDirections extends Migration
{
    public function up()
    {
        Schema::table('srw_azimut_directions', function($table)
        {
            $table->string('image', 255);
        });
    }
    
    public function down()
    {
        Schema::table('srw_azimut_directions', function($table)
        {
            $table->dropColumn('image');
        });
    }
}
