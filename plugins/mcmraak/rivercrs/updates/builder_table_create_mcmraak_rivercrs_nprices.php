<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakRivercrsNprices extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_rivercrs_nprices', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('checkin_id')->unsigned();
            $table->integer('deck_id')->unsigned();
            $table->integer('cabin_id')->unsigned();
            $table->integer('price')->unsigned();
            
            $table->index('checkin_id');
            $table->index('deck_id');
            $table->index('cabin_id');
            
            $table->foreign('checkin_id')
                ->references('id')
                ->on('mcmraak_rivercrs_checkins')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_rivercrs_nprices');
    }
}