<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateMcmraakRivercrsCabins3 extends Migration
{
    public function up()
    {
        Schema::table('mcmraak_rivercrs_cabins', function($table)
        {
            $table->integer('volga_id')->unsigned()->default(0);
            $table->integer('gama_id')->unsigned()->default(0);
            $table->integer('germes_id')->unsigned()->default(0);
            $table->integer('infoflot_id')->unsigned()->default(0);
            $table->integer('waterway_id')->unsigned()->default(0);
        });
    }
    
    public function down()
    {
        Schema::table('mcmraak_rivercrs_cabins', function($table)
        {
            $table->dropColumn('volga_id');
            $table->dropColumn('gama_id');
            $table->dropColumn('germes_id');
            $table->dropColumn('infoflot_id');
            $table->dropColumn('waterway_id');
        });
    }
}
