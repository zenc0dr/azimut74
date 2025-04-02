<?php namespace Srw\Catalog\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateSrwCatalogItems extends Migration
{
    public function up()
    {
        Schema::table('srw_catalog_items', function($table)
        {
            $table->dropColumn('vendorcode');
        });
    }
    
    public function down()
    {
        Schema::table('srw_catalog_items', function($table)
        {
            $table->string('vendorcode', 255);
        });
    }
}
