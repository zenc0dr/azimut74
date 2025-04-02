<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateMcmraakRivercrsKpages3 extends Migration
{
    public function up()
    {
        Schema::table('mcmraak_rivercrs_kpages', function($table)
        {
            $table->smallInteger('in_footer')->unsigned()->default(0);
        });
    }
    
    public function down()
    {
        Schema::table('mcmraak_rivercrs_kpages', function($table)
        {
            $table->dropColumn('in_footer');
        });
    }
}
