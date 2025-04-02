<?php namespace Srw\Catalog\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateSrwCatalogCart extends Migration
{
    public function up()
    {
        Schema::create('srw_catalog_cart', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('item_id');
            $table->integer('quantity');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('srw_catalog_cart');
    }
}
