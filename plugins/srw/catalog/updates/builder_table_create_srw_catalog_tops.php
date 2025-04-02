<?php namespace Srw\Catalog\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateSrwCatalogTops extends Migration
{
    public function up()
    {
        Schema::create('srw_catalog_tops', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('item_id');
            $table->integer('order')->default(100);
            $table->text('desc');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('srw_catalog_tops');
    }
}
