<?php namespace Zen\Actions\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenActionsItems extends Migration
{
    public function up()
    {
        Schema::create('zen_actions_items', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();
            $table->text('code')->nullable();
            $table->text('desc')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_actions_items');
    }
}