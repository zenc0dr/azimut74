<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakRivercrsCruises extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_rivercrs_cruises', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name'); # Имя в ссылке меню круизы
            $table->string('menu_title'); # Имя заголовка "Круизы из"
            $table->string('slug'); # Звено ссылки
            $table->text('text')->nullable(); # Текст сверху seo_articles
            $table->text('seo_articles')->nullable();
            $table->integer('town1')->default(0); # Город откуда в фильтре
            $table->integer('town2')->default(0); # Город куда в фильтре
            $table->integer('active')->default(1); # Активность
            $table->integer('sort_order')->default(0); # Сортировка
            $table->string('metatitle', 255)->nullable();
            $table->string('metadesc', 255)->nullable();
            $table->string('metakey', 255)->nullable();
            $table->string('link')->nullable();
            $table->smallInteger('frame')->default(0);
            $table->text('frame_text')->nullable();
            $table->string('frame_link')->nullable();
            $table->text('frame_code')->nullable();
            $table->dateTime('date1')->nullable();
            $table->dateTime('date2')->nullable();
            $table->integer('days')->default(0);
            $table->integer('ship_id')->default(0);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_rivercrs_cruises');
    }
}