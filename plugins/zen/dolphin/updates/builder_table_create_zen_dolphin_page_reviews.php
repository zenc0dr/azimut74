<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenDolphinPageReviews extends Migration
{
    public function up()
    {
        Schema::create('zen_dolphin_page_reviews', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name');
            $table->text('data')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_dolphin_page_reviews');
    }
}
