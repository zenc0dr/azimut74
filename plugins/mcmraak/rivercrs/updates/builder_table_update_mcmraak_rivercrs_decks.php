<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateMcmraakRivercrsDecks extends Migration
{
    public function up()
    {
        Schema::table('mcmraak_rivercrs_decks', function($table)
        {
            $table->integer('parent_id')->default(0);
        });
    }
    
    public function down()
    {
        Schema::table('mcmraak_rivercrs_decks', function($table)
        {
            $table->dropColumn('parent_id');
        });
    }
}
