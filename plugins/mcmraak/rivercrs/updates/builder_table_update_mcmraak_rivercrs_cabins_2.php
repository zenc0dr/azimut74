<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateMcmraakRivercrsCabins2 extends Migration
{
    public function up()
    {
        Schema::table('mcmraak_rivercrs_cabins', function($table)
        {
            $table->string('infoflot_name')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('mcmraak_rivercrs_cabins', function($table)
        {
            $table->dropColumn('infoflot_name');
        });
    }
}
