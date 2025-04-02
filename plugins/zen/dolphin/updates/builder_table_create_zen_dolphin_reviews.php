<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenDolphinReviews extends Migration
{
    public function up()
    {
        Schema::create('zen_dolphin_reviews', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();
            $table->dateTime('date_time')->nullable();
            $table->text('source_link')->nullable();
            $table->integer('stars')->unsigned()->default(0);
            $table->text('text')->nullable();
            $table->integer('sort_order')->default(0);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_dolphin_reviews');
    }
}