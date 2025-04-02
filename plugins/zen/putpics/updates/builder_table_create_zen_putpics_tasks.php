<?php namespace Zen\Putpics\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenPutpicsTasks extends Migration
{
    public function up()
    {
        Schema::create('zen_putpics_tasks', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('attachment_type')->nullable();
            $table->integer('attachment_id')->unsigned()->default(0);
            $table->integer('photos_count')->unsigned()->default(0);
            $table->integer('user_id')->unsigned()->default(0);
            $table->integer('success')->unsigned()->default(0);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_putpics_tasks');
    }
}
