<?php namespace Srw\Azimut\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSrwAzimutCallme extends Migration
{
    public function up()
    {
        Schema::table('srw_azimut_callme', function($table)
        {
            $table->string('ide', 255)->nullable();
            $table->text('message')->nullable()->unsigned(false)->default(null)->change();
        });
    }
    
    public function down()
    {
        Schema::table('srw_azimut_callme', function($table)
        {
            $table->dropColumn('ide');
            $table->text('message')->nullable()->unsigned(false)->default(null)->change();
        });
    }
}