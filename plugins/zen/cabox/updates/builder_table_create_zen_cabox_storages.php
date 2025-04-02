<?php namespace Zen\Cabox\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenCaboxStorages extends Migration
{
    public function up()
    {
        Schema::create('zen_cabox_storages', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();
            $table->string('code');
            $table->string('path')->nullable();
            $table->integer('time')->default(0);
            $table->smallInteger('one_folder')->default(0);
            $table->smallInteger('compress')->default(1);
        });
    }

    public function down()
    {
        Schema::dropIfExists('zen_cabox_storages');
        @unlink(storage_path('cabox_config.yaml'));
    }
}
