<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateMcmraakRivercrsTowns extends Migration
{
    public function up()
    {
        Schema::table('mcmraak_rivercrs_towns', function($table)
        {
            $table->smallInteger('soft_id')->default(0);
            #$table->smallInteger('hard_id')->default(0);
            $table->string('alt_name')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('mcmraak_rivercrs_towns', function($table)
        {
            $table->dropColumn('soft_id');
            #$table->dropColumn('hard_id');
            $table->dropColumn('alt_name');
        });
    }
}