<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakRivercrsOnboardPivot extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_rivercrs_onboard_pivot', function($table)
        {
            $table->engine = 'InnoDB';
            #$table->increments('id');
            $table->integer('motorship_id');
            $table->integer('onboard_id');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_rivercrs_onboard_pivot');
    }
}