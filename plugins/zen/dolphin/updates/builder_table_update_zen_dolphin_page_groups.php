<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateZenDolphinPageGroups extends Migration
{
    public function up()
    {
        Schema::table('zen_dolphin_page_groups', function($table)
        {
            $table->string('scope')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('zen_dolphin_page_groups', function($table)
        {
            $table->dropColumn('scope');
        });
    }
}
