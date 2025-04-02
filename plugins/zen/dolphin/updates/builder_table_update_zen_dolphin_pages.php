<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateZenDolphinPages extends Migration
{
    public function up()
    {
        Schema::table('zen_dolphin_pages', function($table)
        {
            $table->text('meta_title')->nullable();
            $table->text('meta_description')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('zen_dolphin_pages', function($table)
        {
            $table->dropColumn('meta_title');
            $table->dropColumn('meta_description');
        });
    }
}
