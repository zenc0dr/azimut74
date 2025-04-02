<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateMcmraakRivercrsSettings extends Migration
{
    public function up()
    {
        Schema::create('mcmraak_rivercrs_settings', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
            $table->text('relinks')->nullable();
            $table->text('bookingemails')->nullable();
            $table->text('reviewsemails')->nullable();
            $table->text('banks')->nullable();
        });
//        \Mcmraak\Rivercrs\Models\Settings::insert(
//            ['name'=>'Основные настройки']
//        );
    }
    
    public function down()
    {
        Schema::dropIfExists('mcmraak_rivercrs_settings');
    }
}