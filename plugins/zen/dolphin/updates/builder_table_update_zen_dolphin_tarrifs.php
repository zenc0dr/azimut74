<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateZenDolphinTarrifs extends Migration
{
    public function up()
    {
        Schema::table('zen_dolphin_tarrifs', function($table)
        {
            $table->integer('reduct_id')->nullable();
            $table->string('number_name', 191)->change();
        });
    }
    
    public function down()
    {
        Schema::table('zen_dolphin_tarrifs', function($table)
        {
            $table->dropColumn('reduct_id');
            $table->string('number_name', 191)->change();
        });
    }
}
