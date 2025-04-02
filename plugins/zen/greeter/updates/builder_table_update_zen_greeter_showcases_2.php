<?php namespace Zen\Greeter\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateZenGreeterShowcases2 extends Migration
{
    public function up()
    {
        Schema::table('zen_greeter_showcases', function($table)
        {
            $table->string('color')->nullable()->default('#000000');
        });
    }
    
    public function down()
    {
        Schema::table('zen_greeter_showcases', function($table)
        {
            $table->dropColumn('color');
        });
    }
}
