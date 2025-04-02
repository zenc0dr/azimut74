<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateMcmraakRivercrsMotorships3 extends Migration
{
    public function up()
    {
        Schema::table('mcmraak_rivercrs_motorships', function($table)
        {
            $table->text('not_exist_rooms')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('mcmraak_rivercrs_motorships', function($table)
        {
            $table->dropColumn('not_exist_rooms');
        });
    }
}
