<?php namespace Zen\Worker\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakRivercrsCheckinProbes extends Migration
{
    public function up()
    {
        if (Schema::hasTable('mcmraak_rivercrs_checkin_probes')) {
            return;
        }
        Schema::create('mcmraak_rivercrs_checkin_probes', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('checkin_id')->unsigned();
            $table->string('eds_code', 32);
            $table->integer('eds_id')->unsigned()->default(0);
            $table->integer('motorship_id')->unsigned()->nullable();
            $table->dateTime('run_started_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->dateTime('last_attempt_at')->nullable();
            $table->text('last_error')->nullable();
            $table->string('status', 32)->default('pending');
            $table->unique('checkin_id');
            $table->index(['status', 'eds_code']);
            $table->index('run_started_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('mcmraak_rivercrs_checkin_probes');
    }
}
