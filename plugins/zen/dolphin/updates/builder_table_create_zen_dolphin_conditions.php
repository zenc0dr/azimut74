<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;
use Zen\Dolphin\Classes\Core;

class BuilderTableCreateZenDolphinConditions extends Migration
{
    public function up()
    {
        Schema::create('zen_dolphin_conditions', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();
        });
        Core::fromList('zen_dolphin_conditions');
    }

    public function down()
    {
        Schema::dropIfExists('zen_dolphin_conditions');
    }
}