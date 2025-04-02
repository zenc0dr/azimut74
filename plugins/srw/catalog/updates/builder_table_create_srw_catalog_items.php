<?php namespace Srw\Catalog\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateSrwCatalogItems extends Migration
{
    public function up()
    {
        Schema::create('srw_catalog_items', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
            $table->string('slug');
            $table->integer('category_id')->nullable();
            $table->string('vendorcode')->nullable();
            $table->text('sdesc')->nullable();
            $table->text('fdesc')->nullable();
            $table->integer('price');
            $table->integer('active')->default(1);
            $table->integer('order')->default(100);
            $table->integer('hits')->default(0);
            $table->integer('brand_id')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description')->nullable();
            $table->string('seo_keywords')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->string('tourimage')->nullable();
            $table->string('tourhotel')->nullable();
            $table->string('oldprice')->nullable();
            $table->string('newprice')->nullable();
            $table->integer('duration')->nullable();
            $table->integer('starts')->default(0);
            $table->text('routes')->nullable();
            $table->dateTime('time_start')->nullable();
            $table->dateTime('time_end')->nullable();
            $table->string('consultant')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('srw_catalog_items');
    }
}