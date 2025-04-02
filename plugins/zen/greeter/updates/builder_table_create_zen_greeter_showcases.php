<?php namespace Zen\Greeter\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenGreeterShowcases extends Migration
{
    public function up()
    {
        Schema::create('zen_greeter_showcases', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();
            $table->string('url_entry')->nullable();
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->text('advantages')->nullable();
            $table->string('template_code')->nullable();
            $table->integer('active')->unsigned()->default(1);
        });
    }

    public function down()
    {
        Schema::dropIfExists('zen_greeter_showcases');
    }
}
