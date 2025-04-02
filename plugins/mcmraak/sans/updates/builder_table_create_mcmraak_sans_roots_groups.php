<?php namespace Mcmraak\Sans\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakSansRootsGroups extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_sans_roots_groups', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('root_id');
            $table->integer('group_id');
            $table->integer('sort_order')->default(0);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_sans_roots_groups');
    }
}