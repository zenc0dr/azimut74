<?php namespace Zen\Om\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenOmItems extends Migration
{
    public function up()
    {
        Schema::create('zen_om_items', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();
            $table->string('slug');
            $table->string('url_cache')->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->integer('quantity')->default(0);
            $table->text('short_desc')->nullable();
            $table->text('full_desc')->nullable();
            $table->text('accessories')->nullable();
            $table->smallInteger('active')->default(1);
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->text('seo_keywords')->nullable();
            $table->string('vendor_code')->nullable();
            $table->integer('hits')->default(0);
            $table->integer('category_id')->default(0);
            $table->integer('brand_id')->default(0);
            $table->integer('store_id')->default(1);
            $table->integer('storage_id')->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->integer('sort_order')->default(100);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_om_items');
    }
}