<?php namespace Mcmraak\Sans\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakSansHotelCategories extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_sans_hotel_categories', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
            $table->string('cid')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_sans_hotel_categories');
    }
}