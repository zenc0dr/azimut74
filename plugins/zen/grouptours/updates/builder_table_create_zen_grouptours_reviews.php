<?php namespace Zen\GroupTours\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenGrouptoursReviews extends Migration
{
    public function up()
    {
        Schema::create('zen_grouptours_reviews', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name');
            $table->text('data');
            $table->boolean('active')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('zen_grouptours_reviews');
    }
}