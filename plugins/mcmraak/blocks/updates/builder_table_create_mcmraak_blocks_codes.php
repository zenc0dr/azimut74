<?php namespace Mcmraak\Blocks\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakBlocksCodes extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_blocks_codes', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('code');
            $table->string('replace')->nullable();
            $table->smallInteger('active')->default(1);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_blocks_codes');
    }
}