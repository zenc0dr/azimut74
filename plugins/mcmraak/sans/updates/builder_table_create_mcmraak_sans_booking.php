<?php namespace Mcmraak\Sans\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakSansBooking extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_sans_booking', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->text('data')->nullable();
            $table->smallInteger('active')->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_sans_booking');
    }
}