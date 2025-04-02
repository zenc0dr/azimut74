<?php namespace Srw\Azimut\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSrwAzimutCallme2 extends Migration
{
    public function up()
    {
        Schema::table('srw_azimut_callme', function($table)
        {
            $table->text('message')->nullable()->unsigned(false)->default(null)->change();
        });
    }
    
    public function down()
    {
        Schema::table('srw_azimut_callme', function($table)
        {
            $table->text('message')->nullable()->unsigned(false)->default(null)->change();
        });
    }
}