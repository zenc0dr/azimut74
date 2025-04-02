<?php namespace Zen\GroupTours\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateZenGrouptoursPages extends Migration
{
    public function up()
    {
        Schema::table('zen_grouptours_pages', function($table)
        {
            $table->text('preset')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('zen_grouptours_pages', function($table)
        {
            $table->dropColumn('preset');
        });
    }
}