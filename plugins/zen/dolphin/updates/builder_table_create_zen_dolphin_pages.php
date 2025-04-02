<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenDolphinPages extends Migration
{
    public function up()
    {
        Schema::create('zen_dolphin_pages', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();
            $table->string('label')->nullable();
            $table->string('slug');
            $table->integer('nest_left')->unsigned();
            $table->integer('nest_right')->unsigned();
            $table->integer('nest_depth')->default(0);
            $table->integer('parent_id')->default(0);
            $table->integer('pageblock_id')->unsigned()->default(0);
            $table->text('text')->nullable();
            $table->text('seo_text')->nullable();
            $table->text('preset')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->smallInteger('active')->default(1);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_dolphin_pages');
    }
}