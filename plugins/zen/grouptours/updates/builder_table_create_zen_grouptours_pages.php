<?php namespace Zen\GroupTours\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenGrouptoursPages extends Migration
{
    public function up()
    {
        Schema::create('zen_grouptours_pages', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();
            $table->string('label')->nullable();
            $table->string('slug')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->integer('sort_order')->unsigned()->default(0);
            $table->smallInteger('active')->default(1);
            $table->integer('reviews_block_id')->unsigned()->default(0);
            $table->integer('gallery_block_id')->unsigned()->default(0);
            $table->integer('features_block_id')->unsigned()->default(0);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_grouptours_pages');
    }
}