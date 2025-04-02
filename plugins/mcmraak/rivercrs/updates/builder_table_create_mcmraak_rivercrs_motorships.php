<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakRivercrsMotorships extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_rivercrs_motorships', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
            $table->text('add_a');
            $table->text('add_b');
            $table->text('booking_discounts');
            $table->text('social_discounts');
            $table->text('desc');
            $table->text('youtube');
            $table->string('banner');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->integer('sort_order')->default(0); # Сортировка
            $table->string('metatitle', 255)->nullable();
            $table->string('metadesc', 255)->nullable();
            $table->string('metakey', 255)->nullable();
            $table->integer('waterway_id')->default(0);
            $table->integer('volga_id')->default(0);
            $table->integer('gama_id')->default(0);
            $table->integer('germes_id')->default(0);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_rivercrs_motorships');
    }
}