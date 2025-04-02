<?php namespace Zen\Om\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenOmOrders extends Migration
{
    public function up()
    {
        Schema::create('zen_om_orders', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('status_id')->default(0);
            $table->integer('payment_id')->default(0);
            $table->integer('delivery_id')->default(0);
            $table->text('items');
            $table->text('bag')->nullable();
            $table->text('comments')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->decimal('summ', 15, 2);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_om_orders');
    }
}