<?php namespace Zen\Keeper\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenKeeperBackups extends Migration
{
    public function up()
    {
        Schema::create('zen_keeper_backups', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('site_id')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->integer('size')->unsigned()->default(0);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_keeper_backups');
    }
}