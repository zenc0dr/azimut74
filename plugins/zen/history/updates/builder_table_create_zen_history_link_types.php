<?php namespace Zen\History\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenHistoryLinkTypes extends Migration
{
    public function up()
    {
        Schema::create('zen_history_link_types', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name');
            $table->string('code');
            $table->string('color')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('zen_history_link_types');
    }
}