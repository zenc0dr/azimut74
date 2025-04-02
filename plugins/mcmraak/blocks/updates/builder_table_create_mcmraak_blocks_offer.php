<?php namespace Mcmraak\Blocks\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakBlocksOffer extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_blocks_offer', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->text('offertext')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_blocks_offer');
    }
}