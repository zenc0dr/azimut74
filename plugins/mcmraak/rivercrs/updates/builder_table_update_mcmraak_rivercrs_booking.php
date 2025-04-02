<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateMcmraakRivercrsBooking extends Migration
{
    public function up()
    {
        Schema::table('mcmraak_rivercrs_booking', function($table)
        {
            $table->text('order')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('mcmraak_rivercrs_booking', function($table)
        {
            $table->dropColumn('order');
        });
    }
}
