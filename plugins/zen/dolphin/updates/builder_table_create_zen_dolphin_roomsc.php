<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenDolphinRoomsc extends Migration
{
    public function up()
    {
        Schema::create('zen_dolphin_roomsc', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->integer('sort_order')->default(0);
            $table->string('created_by')->nullable();
            $table->string('eid')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_dolphin_roomsc');
    }
}