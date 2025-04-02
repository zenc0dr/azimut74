<?php namespace Zen\History\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateZenHistoryLinks2 extends Migration
{
    public function up()
    {
        Schema::table('zen_history_links', function($table)
        {
            $table->text('source_data')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('zen_history_links', function($table)
        {
            $table->dropColumn('source_data');
        });
    }
}
