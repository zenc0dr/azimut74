<?php namespace Zen\Sms\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenSmsItems extends Migration
{
    public function up()
    {
        Schema::create('zen_sms_items', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('phone')->nullable();
            $table->text('text')->nullable();
            $table->string('balance')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->integer('profile_id')->default(0);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_sms_items');
    }
}