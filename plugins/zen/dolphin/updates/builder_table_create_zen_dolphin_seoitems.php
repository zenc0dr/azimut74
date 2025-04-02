<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenDolphinSeoitems extends Migration
{
    public function up()
    {
        Schema::create('zen_dolphin_seoitems', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->text('name');
            $table->integer('page_id')->unsigned();
            $table->integer('sort_order')->unsigned()->default(0);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_dolphin_seoitems');
    }
}