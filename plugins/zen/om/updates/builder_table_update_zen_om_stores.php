<?php namespace Zen\Om\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateZenOmStores extends Migration
{
    public function up()
    {
        Schema::table('zen_om_stores', function($table)
        {
            $table->integer('active');
            $table->text('links')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('zen_om_stores', function($table)
        {
            $table->dropColumn('active');
            $table->dropColumn('links');
        });
    }
}
