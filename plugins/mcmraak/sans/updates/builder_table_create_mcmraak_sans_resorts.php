<?php namespace Mcmraak\Sans\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakSansResorts extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_sans_resorts', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
            $table->integer('group_id')->default(0);
            $table->integer('name_block')->default(0);
            $table->string('cid');
            $table->integer('country_id');
            $table->integer('sort_order')->default(0);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_sans_resorts');
    }
}