<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakRivercrsDecks extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_rivercrs_decks', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
            $table->integer('sort_order')->default(0);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_rivercrs_decks');
    }
}
