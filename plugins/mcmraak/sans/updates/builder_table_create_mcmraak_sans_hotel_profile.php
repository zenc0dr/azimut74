<?php namespace Mcmraak\Sans\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakSansHotelProfile extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_sans_hotel_profile', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('cid')->nullable();
            $table->string('hotel_type_name')->nullable();
            $table->text('medical_profile_list')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_sans_hotel_profile');
    }
}