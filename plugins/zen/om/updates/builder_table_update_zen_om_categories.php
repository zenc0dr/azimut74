<?php namespace Zen\Om\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateZenOmCategories extends Migration
{
    public function up()
    {
        Schema::table('zen_om_categories', function($table)
        {
            $table->text('links')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('zen_om_categories', function($table)
        {
            $table->dropColumn('links');
        });
    }
}
