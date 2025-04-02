<?php namespace Zen\Forms\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenFormsItems extends Migration
{
    public function up()
    {
        Schema::create('zen_forms_items', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('code')->nullable();
            $table->text('data');
            $table->string('status')->nullable();
            $table->text('response')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_forms_items');
    }
}