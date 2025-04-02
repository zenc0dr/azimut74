<?php namespace Mcmraak\Sans\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakSansHotels extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_sans_hotels', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
            $table->integer('resort_id')->default(0);
            $table->integer('hotel_category_id')->default(0);
            $table->string('short_name');
            $table->string('cid');
            $table->string('bag_status')->nullable();
            $table->boolean('active')->default(1);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_sans_hotels');
    }
}