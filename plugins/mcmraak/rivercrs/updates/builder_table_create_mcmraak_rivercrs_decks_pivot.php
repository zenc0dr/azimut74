<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakRivercrsDecksPivot extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_rivercrs_decks_pivot', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('cabin_id');
            $table->integer('deck_id');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_rivercrs_decks_pivot');
    }
}