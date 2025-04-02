<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenDolphinPrices extends Migration
{
    public function up()
    {
        Schema::create('zen_dolphin_prices', function($table)
        {
            $table->engine = 'InnoDB';
            $table->date('date')->comment = 'Дата тура';
            $table->integer('tour_id');
            $table->integer('tarrif_id');
            $table->integer('azroom_id');
            $table->integer('pricetype_id');
            $table->integer('price')->default(0);
            $table->integer('age_min')->nullable();
            $table->integer('age_max')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_dolphin_prices');
    }
}