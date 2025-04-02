<?php namespace Zen\Keeper\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenKeeperSites extends Migration
{
    public function up()
    {
        Schema::create('zen_keeper_sites', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();
            $table->string('url')->nullable();
            $table->string('security_token')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_keeper_sites');
    }
}