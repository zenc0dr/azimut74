<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakRivercrsReviews extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_rivercrs_reviews', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('motorship_id')->unsigned();
            $table->string('email')->nullable();
            $table->text('data')->nullable();
            $table->integer('recommended')->default(1);
            $table->integer('active')->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_rivercrs_reviews');
    }
}