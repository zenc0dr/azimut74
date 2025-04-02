<?php namespace Zen\Om\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateZenOmItems extends Migration
{
    public function up()
    {
        Schema::table('zen_om_items', function($table)
        {
            $table->text('frame')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('zen_om_items', function($table)
        {
            $table->dropColumn('frame');
        });
    }
}
