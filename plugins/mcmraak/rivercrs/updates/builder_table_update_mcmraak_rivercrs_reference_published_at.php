<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableUpdateMcmraakRivercrsReferencePublishedAt extends Migration
{
    public function up()
    {
        Schema::table('mcmraak_rivercrs_reference', function ($table) {
            $table->date('published_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('mcmraak_rivercrs_reference', function ($table) {
            $table->dropColumn('published_at');
        });
    }
}
