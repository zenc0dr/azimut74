<?php namespace Zen\Putpics\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateZenPutpicsTasks extends Migration
{
    public function up()
    {
        Schema::table('zen_putpics_tasks', function($table)
        {
            $table->smallInteger('not_found')->unsigned()->default(0);
        });
    }
    
    public function down()
    {
        Schema::table('zen_putpics_tasks', function($table)
        {
            $table->dropColumn('not_found');
        });
    }
}
