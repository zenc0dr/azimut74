<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakRivercrsCheckins extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_rivercrs_checkins', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->dateTime('date');
            $table->dateTime('dateb');
            $table->integer('days');
            $table->integer('motorship_id');
            $table->text('offer')->nullable();
            $table->text('desc_1')->nullable();
            $table->text('desc_2')->nullable();
            $table->text('hot')->nullable();
            $table->smallInteger('special')->default(0);
            $table->smallInteger('active')->default(1);
            $table->string('metatitle', 255)->nullable();
            $table->string('eds_code')->nullable();
            $table->integer('eds_id')->default(0);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_rivercrs_checkins');
    }
}