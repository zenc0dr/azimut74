<?php namespace Zen\Quiz\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenQuizApplications extends Migration
{
    public function up()
    {
        Schema::create('zen_quiz_applications', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('city_id');
            $table->date('date_from');
            $table->date('date_to');
            $table->integer('day_to');
            $table->integer('day_from');
            $table->integer('count_adult');
            $table->integer('count_children');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->boolean('active')->nullable()->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('zen_quiz_applications');
    }
}
