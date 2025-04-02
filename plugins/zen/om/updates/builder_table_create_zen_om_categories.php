<?php namespace Zen\Om\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenOmCategories extends Migration
{
    public function up()
    {
        Schema::create('zen_om_categories', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();
            $table->string('slug');
            $table->string('url_cache')->nullable();
            $table->text('short_desc')->nullable();
            $table->text('full_desc')->nullable();
            $table->smallInteger('active')->default(1);
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->text('seo_keywords')->nullable();
            $table->integer('sort_order')->default(100);
            $table->integer('parent_id')->default(0);
            $table->integer('store_id')->default(1);
            $table->integer('nest_left')->unsigned();
            $table->integer('nest_right')->unsigned();
            $table->integer('nest_depth')->default(0);;
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_om_categories');
    }
}