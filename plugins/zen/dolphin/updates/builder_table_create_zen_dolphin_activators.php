<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenDolphinActivators extends Migration
{
    public function up()
    {
        Schema::create('zen_dolphin_activators', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('reduct_id')->default(0);
            $table->integer('before_of')->nullable();
            $table->integer('before_to')->nullable();
            $table->integer('decrement')->default(0);
            $table->text('title')->nullable();
            $table->text('desc')->nullable();
            $table->smallInteger('accent')->default(0);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_dolphin_activators');
    }
}