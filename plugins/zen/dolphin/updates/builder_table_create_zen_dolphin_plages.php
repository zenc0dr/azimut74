<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenDolphinPlages extends Migration
{
    public function up()
    {
        Schema::create('zen_dolphin_plages', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();
            $table->integer('sort_order')->default(0);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_dolphin_plages');
    }
}
