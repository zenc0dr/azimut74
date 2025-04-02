<?php namespace Mcmraak\Sans\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakSansArticles extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_sans_articles', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->nullable();
            $table->text('text')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->text('seo_keywords')->nullable();
            $table->smallInteger('active')->default(1);
            $table->string('slug')->nullable();
            $table->text('html')->nullable();
            $table->integer('resort_id')->default(0);
            $table->text('seo_articles')->nullable();
            $table->integer('sort_order')->default(0);
            $table->integer('root_id');
            $table->integer('group_id');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_sans_articles');
    }
}