<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;
use Zen\Dolphin\Classes\Core;

class BuilderTableCreateZenDolphinAzcomforts extends Migration
{
    public function up()
    {
        Schema::create('zen_dolphin_azcomforts', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name');
            $table->integer('sort_order')->default(0);
        });

        Core::fromDump('zen_dolphin_azcomforts');
    }

    public function down()
    {
        Schema::dropIfExists('zen_dolphin_azcomforts');
    }
}