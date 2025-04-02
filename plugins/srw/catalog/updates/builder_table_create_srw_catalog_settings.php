<?php namespace Srw\Catalog\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateSrwCatalogSettings extends Migration
{
    public function up()
    {
        Schema::create('srw_catalog_settings', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
            $table->string('value');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('srw_catalog_settings');
    }
}