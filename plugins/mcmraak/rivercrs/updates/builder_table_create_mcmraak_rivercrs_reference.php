<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakRivercrsReference extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_rivercrs_reference', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('slug');
            $table->string('link');
            $table->string('name');
            $table->text('text')->nullable();
            $table->integer('order')->default(0);
            $table->string('metatitle', 255)->nullable();
            $table->string('metakey', 255)->nullable();
            $table->string('metadesc', 255)->nullable();
            $table->smallInteger('menu')->default(1);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_rivercrs_reference');
    }
}