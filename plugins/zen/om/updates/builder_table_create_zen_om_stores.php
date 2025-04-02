<?php namespace Zen\Om\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenOmStores extends Migration
{
    public function up()
    {
        Schema::create('zen_om_stores', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('slug');
            $table->text('desc')->nullable();
            $table->integer('sort_order')->default(0);
        });

        $shop = new \Zen\Om\Models\Store;
        $shop->name = 'Catalog';
        $shop->slug = 'catalog';
        $shop->save();
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_om_stores');
    }
}