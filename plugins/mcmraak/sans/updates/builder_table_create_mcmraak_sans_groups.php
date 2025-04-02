<?php namespace Mcmraak\Sans\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakSansGroups extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_sans_groups', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('slug');
            $table->text('desc')->nullable();
            $table->integer('sort_order')->default(0);
            $table->integer('parent_id');
            $table->integer('nest_left')->unsigned();
            $table->integer('nest_right')->unsigned();
            $table->integer('nest_depth')->default(0);
            $table->integer('default_resort_id')->default(0);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_sans_groups');
    }
}