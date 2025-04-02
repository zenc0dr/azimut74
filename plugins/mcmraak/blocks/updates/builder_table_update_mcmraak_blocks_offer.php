<?php namespace Mcmraak\Blocks\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateMcmraakBlocksOffer extends Migration
{
    public function up()
    {
        Schema::table('mcmraak_blocks_offer', function($table)
        {
            $table->string('name', 255)->nullable()->default('Оферта');
            $table->text('offertext')->nullable()->unsigned(false)->default(null)->change();
        });
    }
    
    public function down()
    {
        Schema::table('mcmraak_blocks_offer', function($table)
        {
            $table->dropColumn('name');
            $table->text('offertext')->nullable()->unsigned(false)->default(null)->change();
        });
    }
}