<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenDolphinExtours extends Migration
{
    public function up()
    {
        Schema::create('zen_dolphin_extours', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();
            $table->string('geo_model');
            $table->integer('geo_id');
            $table->text('extours_eids');
            $table->string('created_by')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('zen_dolphin_extours');
    }
}