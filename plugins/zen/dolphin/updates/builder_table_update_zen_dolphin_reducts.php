<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateZenDolphinReducts extends Migration
{
    public function up()
    {
        Schema::table('zen_dolphin_reducts', function($table)
        {
            $table->text('desc')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('zen_dolphin_reducts', function($table)
        {
            $table->dropColumn('desc');
        });
    }
}
