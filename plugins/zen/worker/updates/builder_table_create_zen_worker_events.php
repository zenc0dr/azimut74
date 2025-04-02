<?php namespace Zen\Worker\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenWorkerEvents extends Migration
{
    public function up()
    {
        Schema::create('zen_worker_events', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();
            $table->text('data')->nullable();
            $table->text('code')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->smallInteger('success')->unsigned()->default(0);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_worker_events');
    }
}