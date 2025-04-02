<?php namespace Zen\Deprecator\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenDeprecatorAttempts extends Migration
{
    public function up()
    {
        Schema::create('zen_deprecator_attempts', function ($table) {
            $table->engine = 'InnoDB';
            $table->string('signature');
            $table->dateTime('time');
            $table->primary(['signature']);
            $table->index('signature');
        });
    }

    public function down()
    {
        Schema::dropIfExists('zen_deprecator_attempts');
    }
}