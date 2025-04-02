<?php namespace Zen\Om\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenOmProfiles extends Migration
{
    public function up()
    {
        Schema::create('zen_om_profiles', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('user_id')->unsigned()->index();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('postal_code')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_om_profiles');
    }
}