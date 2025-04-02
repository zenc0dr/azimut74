<?php namespace Zen\Worker\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenWorkerStreams extends Migration
{
    public function up()
    {
        Schema::create('zen_worker_streams', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();
            $table->string('code');
            $table->text('data')->nullable();
            $table->text('pools')->nullable();
            $table->smallInteger('active')->unsigned()->default(1);
            $table->integer('sort_order')->unsigned()->default(0);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_worker_streams');
    }
}