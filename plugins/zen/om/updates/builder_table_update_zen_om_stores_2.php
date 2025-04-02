<?php namespace Zen\Om\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateZenOmStores2 extends Migration
{
    public function up()
    {
        Schema::table('zen_om_stores', function($table)
        {
            $table->integer('active')->default(1)->change();
        });
    }
    
    public function down()
    {
        Schema::table('zen_om_stores', function($table)
        {
            $table->integer('active')->default(null)->change();
        });
    }
}
