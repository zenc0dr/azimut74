<?php namespace Zen\GroupTours\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenGrouptoursToursTags extends Migration
{
    public function up()
    {
        Schema::create('zen_grouptours_tours_tags_pivot', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('tour_id')->unsigned();
            $table->integer('tag_id')->unsigned();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_grouptours_tours_tags');
    }
}