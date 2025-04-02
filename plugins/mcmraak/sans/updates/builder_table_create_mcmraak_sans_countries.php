<?php namespace Mcmraak\Sans\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakSansCountries extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_sans_countries', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
            $table->smallInteger('name_block')->default(0);
            $table->string('cid');
            $table->integer('sort_order')->default(0);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_sans_countries');
    }
}