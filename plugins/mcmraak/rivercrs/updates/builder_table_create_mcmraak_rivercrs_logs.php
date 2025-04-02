<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakRivercrsLogs extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_rivercrs_logs', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('type')->nullable();
            $table->string('method')->nullable();
            $table->text('text')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->text('url')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_rivercrs_logs');
    }
}