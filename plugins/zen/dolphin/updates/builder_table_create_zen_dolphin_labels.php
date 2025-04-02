<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenDolphinLabels extends Migration
{
    public function up()
    {
        Schema::create('zen_dolphin_labels', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_dolphin_labels');
    }
}
