<?php namespace Zen\Sms\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateZenSmsProfiles extends Migration
{
    public function up()
    {
        Schema::table('zen_sms_profiles', function($table)
        {
            $table->string('from')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('zen_sms_profiles', function($table)
        {
            $table->dropColumn('from');
        });
    }
}
