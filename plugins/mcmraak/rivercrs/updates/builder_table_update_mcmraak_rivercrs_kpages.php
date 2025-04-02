<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateMcmraakRivercrsKpages extends Migration
{
    public function up()
    {
        Schema::table('mcmraak_rivercrs_kpages', function($table)
        {
            $table->smallInteger('active')->unsigned()->default(1);
            $table->smallInteger('in_menu')->unsigned()->default(1);
        });
    }
    
    public function down()
    {
        Schema::table('mcmraak_rivercrs_kpages', function($table)
        {
            $table->dropColumn('active');
            $table->dropColumn('in_menu');
        });
    }
}