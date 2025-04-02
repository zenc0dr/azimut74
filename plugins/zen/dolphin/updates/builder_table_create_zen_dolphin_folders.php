<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenDolphinFolders extends Migration
{
    public function up()
    {
        Schema::create('zen_dolphin_folders', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();
            $table->string('code');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_dolphin_folders');
    }
}