<?php namespace Zen\Sms\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenSmsProfiles extends Migration
{
    public function up()
    {
        Schema::create('zen_sms_profiles', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('code')->nullable();
            $table->string('key')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_sms_profiles');
    }
}
