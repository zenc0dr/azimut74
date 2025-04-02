<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakRivercrsKtypes extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_rivercrs_ktypes', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->text('name')->nullable();
            $table->integer('sort_order')->unsigned()->default(0);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_rivercrs_ktypes');
    }
}