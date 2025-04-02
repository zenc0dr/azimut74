<?php namespace Zen\Om\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenOmFields extends Migration
{
    public function up()
    {
        Schema::create('zen_om_fields', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('code');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_om_fields');
    }
}