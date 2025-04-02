<?php namespace Zen\Cabox\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateZenCaboxStorages2 extends Migration
{
    public function up()
    {
        Schema::table('zen_cabox_storages', function($table)
        {
            $table->text('handlers')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('zen_cabox_storages', function($table)
        {
            $table->dropColumn('handlers');
        });
    }
}