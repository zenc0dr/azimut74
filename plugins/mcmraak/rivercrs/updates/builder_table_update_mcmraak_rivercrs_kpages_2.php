<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateMcmraakRivercrsKpages2 extends Migration
{
    public function up()
    {
        Schema::table('mcmraak_rivercrs_kpages', function($table)
        {
            $table->text('slider')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('mcmraak_rivercrs_kpages', function($table)
        {
            $table->dropColumn('slider');
        });
    }
}
