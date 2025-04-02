<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenDolphinTarrifs extends Migration
{
    public function up()
    {
        Schema::create('zen_dolphin_tarrifs', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();
            $table->string('number_name')->nullable()->comment = 'Название номера';
            
            # Relations
            $table->integer('tour_id');
            $table->integer('operator_id')->default(0);
            $table->integer('azcomfort_id')->default(0);
            $table->integer('azpansion_id')->default(0);
            $table->integer('hotel_id')->default(0);
            
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->smallInteger('active')->default(1);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_dolphin_tarrifs');
    }
}