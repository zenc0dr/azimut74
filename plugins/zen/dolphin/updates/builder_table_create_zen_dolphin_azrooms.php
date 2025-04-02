<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;
use Zen\Dolphin\Classes\Core;

class BuilderTableCreateZenDolphinAzrooms extends Migration
{
    public function up()
    {
        Schema::create('zen_dolphin_azrooms', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();
            $table->integer('sort_order')->default(0);
        });

        Core::fromScript('zen_dolphin_azrooms');
    }

    public function down()
    {
        Schema::dropIfExists('zen_dolphin_azrooms');
    }
}