<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakRivercrsWaybills extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_rivercrs_waybills', function($table)
        {
            $table->engine = 'InnoDB';
            #$table->increments('id');
            $table->integer('checkin_id');
            $table->integer('town_id');
            $table->integer('order');
            $table->text('excursion');
            $table->smallInteger('bold');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_rivercrs_waybills');
    }
}