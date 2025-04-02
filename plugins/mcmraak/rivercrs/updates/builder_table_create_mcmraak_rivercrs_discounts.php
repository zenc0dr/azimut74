<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakRivercrsDiscounts extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_rivercrs_discounts', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();
            $table->string('image')->nullable();
            $table->string('title')->nullable();
            $table->string('overlap_title')->nullable();
            $table->text('desc')->nullable();
            $table->dateTime('valid_until')->nullable();
            $table->integer('overlap_activation')->default(0);
            $table->text('motorships')->nullable();
            $table->integer('sort_order')->default(0);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_rivercrs_discounts');
    }
}