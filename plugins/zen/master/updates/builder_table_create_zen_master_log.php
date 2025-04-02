<?php namespace Zen\Master\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenMasterLog extends Migration
{
    public function up()
    {
        Schema::create('zen_master_log', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('event_name')->nullable();
            $table->text('data')->nullable();
            $table->string('ip')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_master_log');
    }
}
