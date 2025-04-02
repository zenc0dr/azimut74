<?php namespace Zen\Om\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateZenOmItems2 extends Migration
{
    public function up()
    {
        Schema::table('zen_om_items', function($table)
        {
            $table->text('itemlink')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('zen_om_items', function($table)
        {
            $table->dropColumn('itemlink');
        });
    }
}
