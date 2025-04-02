<?php namespace Srw\Catalog\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSrwCatalogItems3 extends Migration
{
    public function up()
    {
        Schema::table('srw_catalog_items', function($table)
        {
            $table->text('address')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('srw_catalog_items', function($table)
        {
            $table->dropColumn('address');
        });
    }
}