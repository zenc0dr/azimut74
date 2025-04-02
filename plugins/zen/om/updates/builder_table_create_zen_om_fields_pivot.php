<?php namespace Zen\Om\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenOmFieldsPivot extends Migration
{
    public function up()
    {
        Schema::create('zen_om_fields_pivot', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('item_id');
            $table->integer('field_id');
            $table->integer('int_val')->nullable();
            $table->string('str_val')->nullable();
            $table->text('html_val')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_om_fields_pivot');
    }
}