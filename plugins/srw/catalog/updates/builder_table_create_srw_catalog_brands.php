<?php namespace Srw\Catalog\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateSrwCatalogBrands extends Migration
{
    public function up()
    {
        Schema::create('srw_catalog_brands', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
            $table->string('image');
            $table->integer('order')->default(100);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('srw_catalog_brands');
    }
}