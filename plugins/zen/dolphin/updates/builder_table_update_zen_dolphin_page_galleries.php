<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateZenDolphinPageGalleries extends Migration
{
    public function up()
    {
        Schema::table('zen_dolphin_page_galleries', function($table)
        {
            $table->text('title')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('zen_dolphin_page_galleries', function($table)
        {
            $table->dropColumn('title');
        });
    }
}
