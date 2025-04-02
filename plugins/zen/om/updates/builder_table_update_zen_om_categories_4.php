<?php namespace Zen\Om\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateZenOmCategories4 extends Migration
{
    public function up()
    {
        Schema::table('zen_om_categories', function($table)
        {
            $table->string('card_image', 255)->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('zen_om_categories', function($table)
        {
            $table->dropColumn('card_image');
        });
    }
}
