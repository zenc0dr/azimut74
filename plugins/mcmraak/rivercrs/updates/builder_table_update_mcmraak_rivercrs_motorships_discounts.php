<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateMcmraakRivercrsMotorshipsDiscounts extends Migration
{
    public function up()
    {
        Schema::table('mcmraak_rivercrs_motorships', function($table)
        {
            $table->text('permanent_discounts')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('mcmraak_rivercrs_motorships', function($table)
        {
            $table->dropColumn('permanent_discounts');
        });
    }
}