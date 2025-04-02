<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateMcmraakRivercrsMotorships2 extends Migration
{
    public function up()
    {
        Schema::table('mcmraak_rivercrs_motorships', function($table)
        {
            $table->text('exist_rooms')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('mcmraak_rivercrs_motorships', function($table)
        {
            $table->dropColumn('exist_rooms');
        });
    }
}
