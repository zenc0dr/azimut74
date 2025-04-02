<?php namespace Mcmraak\Sans\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakSansPages extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_sans_pages', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->nullable();
            $table->text('search_preset')->nullable();
            $table->string('slug');
            $table->text('text')->nullable();
            $table->text('seo_articles')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->text('seo_keywords')->nullable();
            $table->smallInteger('active')->default(1);
            $table->integer('sort_order')->default(0);
            $table->integer('resort_id')->default(0);
            $table->integer('root_id')->default(0);
            $table->smallInteger('search_preset_active')->default(0);
            $table->boolean('is_show_group')->default(0);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_sans_pages');
    }
}