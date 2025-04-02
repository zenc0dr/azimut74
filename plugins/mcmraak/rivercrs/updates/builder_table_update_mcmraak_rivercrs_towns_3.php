<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateMcmraakRivercrsTowns3 extends Migration
{
    public function up()
    {
        Schema::table('mcmraak_rivercrs_towns', function($table)
        {
            $table->smallInteger('active')->default(1);
        });
    }
    
    public function down()
    {
        Schema::table('mcmraak_rivercrs_towns', function($table)
        {
            $table->dropColumn('active');
        });
    }
}
