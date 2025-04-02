<?php namespace Srw\Catalog\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSrwCatalogItems2 extends Migration
{
    public function up()
    {
        Schema::table('srw_catalog_items', function($table)
        {
            $table->text('sdesc')->nullable()->change();
            $table->text('fdesc')->nullable()->change();
            $table->integer('price')->nullable()->change();
            $table->integer('hits')->nullable()->change();
        });
    }
    
    public function down()
    {
        Schema::table('srw_catalog_items', function($table)
        {
            $table->text('sdesc')->nullable(false)->change();
            $table->text('fdesc')->nullable(false)->change();
            $table->integer('price')->nullable(false)->change();
            $table->integer('hits')->nullable(false)->change();
        });
    }
}
