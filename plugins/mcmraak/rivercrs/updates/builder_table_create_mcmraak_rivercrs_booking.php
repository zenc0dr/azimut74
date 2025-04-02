<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakRivercrsBooking extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_rivercrs_booking', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('checkin_id');
            $table->text('cabins');
            $table->string('name');
            $table->string('phone');
            $table->string('email');
            $table->integer('peoples')->default(1);
            $table->text('desc')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->integer('processed')->default(0);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_rivercrs_booking');
    }
}