<?php namespace Zen\Cabox\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateZenCaboxStorages extends Migration
{
    public function up()
    {
        Schema::table('zen_cabox_storages', function($table)
        {
            $table->smallInteger('images')->default(0);
            @unlink(storage_path('cabox_config.yaml'));
        });
    }

    public function down()
    {
        Schema::table('zen_cabox_storages', function($table)
        {
            $table->dropColumn('images');
        });
    }
}
