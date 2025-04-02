<?php namespace Zen\Keeper\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateZenKeeperSites extends Migration
{
    public function up()
    {
        Schema::table('zen_keeper_sites', function($table)
        {
            $table->smallInteger('active')->default(1);
        });
    }
    
    public function down()
    {
        Schema::table('zen_keeper_sites', function($table)
        {
            $table->dropColumn('active');
        });
    }
}