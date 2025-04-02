<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenDolphinFaq extends Migration
{
    public function up()
    {
        Schema::create('zen_dolphin_faq', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();
            $table->text('data')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_dolphin_faq');
    }
}