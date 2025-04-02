<?php namespace Zen\History\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenHistoryLinks extends Migration
{
    public function up()
    {
        Schema::create('zen_history_links', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('visiter_id');
            $table->text('url');
            $table->text('title')->nullable();
            $table->integer('inner_id');
            $table->boolean('is_delete')->default(false);
            $table->integer('type_id');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('zen_history_links');
    }
}
