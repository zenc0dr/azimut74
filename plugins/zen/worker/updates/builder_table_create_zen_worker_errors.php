<?php namespace Zen\Worker\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenWorkerErrors extends Migration
{
    public function up()
    {
        Schema::create('zen_worker_errors', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('stream_id')->unsigned();
            $table->string('call')->nullable();
            $table->text('data')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_worker_errors');
    }
}