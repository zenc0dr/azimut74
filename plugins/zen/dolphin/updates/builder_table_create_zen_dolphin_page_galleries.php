<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenDolphinPageGalleries extends Migration
{
    public function up()
    {
        Schema::create('zen_dolphin_page_galleries', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_dolphin_page_galleries');
    }
}