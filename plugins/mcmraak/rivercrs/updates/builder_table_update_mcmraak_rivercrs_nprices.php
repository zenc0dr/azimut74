<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateMcmraakRivercrsNprices extends Migration
{
    public function up()
    {
        Schema::table('mcmraak_rivercrs_nprices', function($table)
        {
            $table->integer('places_qnt')->unsigned()->default(0)->after('cabin_id');
        });
    }
    
    public function down()
    {
        Schema::table('mcmraak_rivercrs_nprices', function($table)
        {
            $table->dropColumn('places_qnt');
        });
    }
}
