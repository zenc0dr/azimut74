<?php namespace Zen\Om\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenOmSettings extends Migration
{
    public function up()
    {
        Schema::create('zen_om_settings', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->smallInteger('active')->default(0);
            $table->string('name')->nullable();
            $table->text('settings');
        });
        \Zen\Om\Models\Setting::insert([
            'name' => 'Main settings',
            'active' => 1,
            'settings' => '{"item_htmlfix":0,"category_htmlfix":0,"identifier":"id"}'
        ]);
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_om_settings');
    }
}