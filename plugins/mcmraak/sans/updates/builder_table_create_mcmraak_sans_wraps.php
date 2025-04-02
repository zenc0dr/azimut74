<?php namespace Mcmraak\Sans\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakSansWraps extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_sans_wraps', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('resort_id');
            $table->integer('type_id');
            $table->integer('page_id');
            $table->string('name');
            $table->integer('sort_order')->default(0);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_sans_wraps');
    }
}