<?php namespace Zen\Om\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateZenOmCategories11 extends Migration
{
    public function up()
    {
        Schema::table('zen_om_categories', function($table)
        {
            $table->smallInteger('leftmargin_1')->default(0);
            $table->smallInteger('leftmargin_2')->default(0);
        });
    }
    
    public function down()
    {
        Schema::table('zen_om_categories', function($table)
        {
            $table->dropColumn('leftmargin_1');
            $table->dropColumn('leftmargin_2');
        });
    }
}
