<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenDolphinRegions extends Migration
{
    public function up()
    {
        Schema::create('zen_dolphin_regions', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();
            $table->integer('virtual_id')->default(0);
            $table->integer('country_id')->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->integer('sort_order')->default(0);
            $table->string('created_by')->nullable();
            $table->string('eid')->nullable();
            $table->text('thumbs')->nullable()->comment = 'Кэш сниппетов';
        });
    }

    public function down()
    {
        Schema::dropIfExists('zen_dolphin_regions');
    }
}
