<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenDolphinPageGroupPivot extends Migration
{
    public function up()
    {
        Schema::create('zen_dolphin_page_group_pivot', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('page_id')->unsigned();
            $table->integer('group_id')->unsigned();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_dolphin_page_group_pivot');
    }
}