<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateMcmraakRivercrsMotorships5 extends Migration
{
    public function up()
    {
        Schema::table('mcmraak_rivercrs_motorships', function($table)
        {
            $table->string('extra_name')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('mcmraak_rivercrs_motorships', function($table)
        {
            $table->dropColumn('extra_name');
        });
    }
}
