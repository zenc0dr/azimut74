<?php namespace Mcmraak\Sans\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakSansRoots extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_sans_roots', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('slug');
            $table->integer('default_group_id')->nullable();
            $table->integer('sort_order')->default(100);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_sans_roots');
    }
}