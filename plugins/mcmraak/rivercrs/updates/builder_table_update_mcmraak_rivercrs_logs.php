<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateMcmraakRivercrsLogs extends Migration
{
    public function up()
    {
        Schema::table('mcmraak_rivercrs_logs', function($table)
        {
            $table->string('eds_code')->nullable();
            $table->string('title')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('mcmraak_rivercrs_logs', function($table)
        {
            $table->dropColumn('eds_code');
            $table->dropColumn('title');
        });
    }
}
