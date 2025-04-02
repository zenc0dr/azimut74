<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenDolphinBlocksFoldersPivot extends Migration
{
    public function up()
    {
        Schema::create('zen_dolphin_blocks_folders_pivot', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('block_id');
            $table->integer('folder_id');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_dolphin_blocks_folders_pivot');
    }
}