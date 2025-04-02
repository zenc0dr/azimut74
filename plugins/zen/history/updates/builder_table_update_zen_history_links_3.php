<?php namespace Zen\History\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateZenHistoryLinks3 extends Migration
{
    public function up()
    {
        Schema::table('zen_history_links', function($table)
        {
            $table->text('url')->nullable()->change();
            $table->integer('inner_id')->nullable()->change();
        });
    }
    
    public function down()
    {
        Schema::table('zen_history_links', function($table)
        {
            $table->text('url')->nullable(false)->change();
            $table->integer('inner_id')->nullable(false)->change();
        });
    }
}
