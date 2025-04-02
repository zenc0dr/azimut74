<?php namespace Mcmraak\Blocks\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateMcmraakBlocksSlider3 extends Migration
{
    public function up()
    {
        Schema::table('mcmraak_blocks_slider', function($table)
        {
            $table->dropColumn('image');
            $table->dropColumn('sltop');
            $table->dropColumn('slmain');
            $table->dropColumn('link');
            $table->dropColumn('price');
            $table->dropColumn('order');
        });
    }
    
    public function down()
    {
        Schema::table('mcmraak_blocks_slider', function($table)
        {
            $table->string('image', 255);
            $table->string('sltop', 255)->nullable();
            $table->string('slmain', 255)->nullable();
            $table->string('link', 255)->nullable();
            $table->string('price', 255)->nullable();
            $table->integer('order')->nullable()->default(100);
        });
    }
}
