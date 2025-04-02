<?php namespace Zen\Fetcher\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenFetcherPools extends Migration
{
    public function up()
    {
        Schema::create('zen_fetcher_pools', function($table)
        {
            $table->string('code')->unique();
            $table->primary(['code']);
            $table->string('name');
            $table->text('desc')->nullable();
            $table->boolean('active')->default(true);
            $table->integer('sort_order')->default(0);
        });
    }

    public function down()
    {
        Schema::dropIfExists('zen_fetcher_pools');
    }
}