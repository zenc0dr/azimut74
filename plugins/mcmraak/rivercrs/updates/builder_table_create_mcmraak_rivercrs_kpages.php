<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakRivercrsKpages extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_rivercrs_kpages', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();
            $table->string('menu_item')->nullable();
            $table->string('slug')->nullable();
            $table->integer('ktype_id')->unsigned()->default(0);
            $table->integer('sort_order')->unsigned()->default(0);
            $table->text('preset')->nullable();
            $table->text('data')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_rivercrs_kpages');
    }
}