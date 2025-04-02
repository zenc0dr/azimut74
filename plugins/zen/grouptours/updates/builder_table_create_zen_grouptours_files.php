<?php namespace Zen\GroupTours\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenGrouptoursFiles extends Migration
{
    public function up()
    {
        Schema::create('zen_grouptours_files', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();
            $table->text('desc')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_grouptours_files');
    }
}