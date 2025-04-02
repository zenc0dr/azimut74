<?php namespace Zen\History\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateZenHistoryLinks extends Migration
{
    public function up()
    {
        Schema::table('zen_history_links', function($table)
        {
            $table->text('days')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('zen_history_links', function($table)
        {
            $table->dropColumn('days');
        });
    }
}