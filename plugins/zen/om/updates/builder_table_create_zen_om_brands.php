<?php namespace Zen\Om\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenOmBrands extends Migration
{
    public function up()
    {
        Schema::create('zen_om_brands', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('link')->nullable();
            $table->text('desc')->nullable();
            $table->string('slug');
            $table->integer('sort_order')->default(0);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_om_brands');
    }
}