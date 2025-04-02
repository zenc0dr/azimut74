<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;
use Zen\Dolphin\Classes\Core;

class BuilderTableCreateZenDolphinOperators extends Migration
{
    public function up()
    {
        Schema::create('zen_dolphin_operators', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();
            $table->text('desc')->nullable();
        });
        Core::fromList('zen_dolphin_operators');
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_dolphin_operators');
    }
}