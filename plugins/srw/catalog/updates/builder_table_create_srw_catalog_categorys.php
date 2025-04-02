<?php namespace Srw\Catalog\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateSrwCatalogCategorys extends Migration
{
    public function up()
    {
        Schema::create('srw_catalog_categorys', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('slug');
            $table->string('name');
            $table->integer('parent_id')->nullable();
            $table->text('desc');
            $table->string('image');
            $table->integer('order')->default(100);
            $table->integer('nest_left')->unsigned();
            $table->integer('nest_right')->unsigned();
            $table->integer('nest_depth');
            $table->string('seo_title')->nullable();
            $table->string('seo_description')->nullable();
            $table->string('seo_keywords')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('srw_catalog_categorys');
    }
}