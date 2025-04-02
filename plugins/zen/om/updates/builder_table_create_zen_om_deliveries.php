<?php namespace Zen\Om\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenOmDeliveries extends Migration
{
    public function up()
    {
        Schema::create('zen_om_deliveries', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->nullable();
            $table->text('desc')->nullable();
            $table->integer('sort_order')->default(0);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_om_deliveries');
    }
}