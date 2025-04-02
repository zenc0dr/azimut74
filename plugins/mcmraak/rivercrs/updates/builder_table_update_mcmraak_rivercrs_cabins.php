<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateMcmraakRivercrsCabins extends Migration
{
    public function up()
    {
        Schema::table('mcmraak_rivercrs_cabins', function($table)
        {
            $table->string('waterway_name')->nullable();
            $table->string('volga_name')->nullable();
            $table->string('gama_name')->nullable();
            $table->string('germes_name')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('mcmraak_rivercrs_cabins', function($table)
        {
            $table->dropColumn('waterway_name');
            $table->dropColumn('volga_name');
            $table->dropColumn('gama_name');
            $table->dropColumn('germes_name');
        });
    }
}