<?php namespace Zen\Greeter\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateZenGreeterShowcases extends Migration
{
    public function up()
    {
        Schema::table('zen_greeter_showcases', function($table)
        {
            $table->integer('opacity')->unsigned()->default(30);
        });
    }
    
    public function down()
    {
        Schema::table('zen_greeter_showcases', function($table)
        {
            $table->dropColumn('opacity');
        });
    }
}
