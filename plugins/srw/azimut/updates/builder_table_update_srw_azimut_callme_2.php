<?php namespace Srw\Azimut\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSrwAzimutCallme_2 extends Migration
{
    public function up()
    {
        Schema::table('srw_azimut_callme', function($table)
        {
            $table->string('ip')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('srw_azimut_callme', function($table)
        {
            $table->dropColumn('ip');
        });
    }
}