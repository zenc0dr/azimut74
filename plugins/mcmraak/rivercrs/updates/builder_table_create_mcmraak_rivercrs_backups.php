<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakRivercrsBackups extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_rivercrs_backups', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->nullable();
            $table->integer('size')->default(0);
            $table->text('filename');
            $table->boolean('last_restore')->default(0);
            $table->dateTime('date')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_rivercrs_backups');
    }
}