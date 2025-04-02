<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakRivercrsCheckinsMemory extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_rivercrs_checkins_memory', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('checkin_id')->unsigned();
            $table->timestamp('updated_at');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_rivercrs_checkins_memory');
    }
}
