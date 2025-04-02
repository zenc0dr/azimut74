<?php namespace Zen\Om\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateZenOmCategories2 extends Migration
{
    public function up()
    {
        Schema::table('zen_om_categories', function($table)
        {
            $table->text('cat_links')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('zen_om_categories', function($table)
        {
            $table->dropColumn('cat_links');
        });
    }
}
