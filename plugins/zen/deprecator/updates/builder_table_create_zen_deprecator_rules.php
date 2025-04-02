<?php namespace Zen\Deprecator\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenDeprecatorRules extends Migration
{
    public function up()
    {
        Schema::create('zen_deprecator_rules', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->text('name');
            $table->text('code');
            $table->mediumText('answer')->nullable();
            $table->integer('ttl')->unsigned();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_deprecator_rules');
    }
}