<?php namespace Zen\GroupTours\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenGrouptoursTags extends Migration
{
    public function up()
    {
        Schema::create('zen_grouptours_tags', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();
            $table->integer('sort_order')->default(0);
            $table->smallInteger('active')->unsigned()->default(1);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_grouptours_tags');
    }
}