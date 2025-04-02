<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenDolphinOrders extends Migration
{
    public function up()
    {
        Schema::create('zen_dolphin_orders', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->text('data')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->smallInteger('processed')->default(0);
            $table->text('comment')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_dolphin_orders');
    }
}