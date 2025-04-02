<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakRivercrsTransit extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_rivercrs_transit', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
            $table->string('menu_title');
            $table->string('slug');
            $table->text('text')->nullable();
            $table->text('seo_articles')->nullable();
            $table->integer('town1')->default(0);
            $table->integer('town2')->default(0);
            $table->integer('active')->default(1);
            $table->integer('sort_order')->default(0);
            $table->integer('parent_id');
            $table->string('metatitle', 255)->nullable();
            $table->string('metadesc', 255)->nullable();
            $table->string('metakey', 255)->nullable();
            $table->smallInteger('menu')->default(1);
            $table->dateTime('date1')->nullable();
            $table->dateTime('date2')->nullable();
            $table->integer('ship_id')->default(0);
            $table->integer('days')->default(0);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_rivercrs_transit');
    }
}