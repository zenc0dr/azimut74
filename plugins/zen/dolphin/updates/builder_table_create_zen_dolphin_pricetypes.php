<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;
use Zen\Dolphin\Classes\Core;

class BuilderTableCreateZenDolphinPricetypes extends Migration
{
    public function up()
    {
        Schema::create('zen_dolphin_pricetypes', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();
            $table->string('code');
            $table->integer('sort_order')->default(0);
        });
        Core::fromDump('zen_dolphin_pricetypes');
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_dolphin_pricetypes');
    }
}