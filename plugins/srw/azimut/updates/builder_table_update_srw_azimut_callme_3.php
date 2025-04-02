<?php namespace Srw\Azimut\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSrwAzimutCallme3 extends Migration
{
    public function up()
    {
        Schema::table('srw_azimut_callme', function($table)
        {
            $table->string('tour', 255);
        });
    }
    
    public function down()
    {
        Schema::table('srw_azimut_callme', function($table)
        {
            $table->dropColumn('tour');
        });
    }
}
