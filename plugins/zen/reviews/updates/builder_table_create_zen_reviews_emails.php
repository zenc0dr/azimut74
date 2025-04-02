<?php namespace Zen\Reviews\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenReviewsEmails extends Migration
{
    public function up()
    {
        Schema::create('zen_reviews_emails', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('email');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_reviews_emails');
    }
}