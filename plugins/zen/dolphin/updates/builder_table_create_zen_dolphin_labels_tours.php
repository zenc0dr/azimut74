<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenDolphinLabelsTours extends Migration
{
    public function up()
    {
        Schema::create('zen_dolphin_labels_tours', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('label_id')->unsigned();
            $table->integer('tour_id')->unsigned();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_dolphin_labels_tours');
    }
}
