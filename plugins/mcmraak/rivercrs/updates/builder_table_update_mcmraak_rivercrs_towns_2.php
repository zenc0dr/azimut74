<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateMcmraakRivercrsTowns2 extends Migration
{
    public function up()
    {
        Schema::table('mcmraak_rivercrs_towns', function($table)
        {
            $table->string('eds_code')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('mcmraak_rivercrs_towns', function($table)
        {
            $table->dropColumn('eds_code');
        });
    }
}
