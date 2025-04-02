<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateMcmraakRivercrsMotorships6 extends Migration
{
    public function up()
    {
        Schema::table('mcmraak_rivercrs_motorships', function($table)
        {
            $table->integer('status_id')->unsigned()->default(0);
        });
    }
    
    public function down()
    {
        Schema::table('mcmraak_rivercrs_motorships', function($table)
        {
            $table->dropColumn('status_id');
        });
    }
}
