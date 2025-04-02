<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenDolphinHservicesPivot extends Migration
{
    public function up()
    {
        Schema::create('zen_dolphin_hservices_pivot', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('hotel_id');
            $table->integer('service_id');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_dolphin_hservices_pivot');
    }
}