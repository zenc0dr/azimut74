<?php namespace Zen\GroupTours\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenGroupToursFeatures extends Migration
{
    public function up()
    {
        Schema::create('zen_grouptours_features', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();
            $table->text('title')->nullable();
            $table->text('sub_title')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('zen_grouptours_features');
    }
}