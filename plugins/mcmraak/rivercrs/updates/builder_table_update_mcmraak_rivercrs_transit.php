<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateMcmraakRivercrsTransit extends Migration
{
    public function up()
    {
        Schema::table('mcmraak_rivercrs_transit', function($table)
        {
            $table->string('h1')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('mcmraak_rivercrs_transit', function($table)
        {
            $table->dropColumn('h1');
        });
    }
}