<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenDolphinReductsPivot extends Migration
{
    public function up()
    {
        Schema::create('zen_dolphin_reducts_pivot', function($table)
        {
            $table->engine = 'InnoDB';
            $table->date('date');
            $table->integer('tarrif_id')->unsigned()->default(0);
            $table->integer('reduct_id')->unsigned()->default(0);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_dolphin_reducts_pivot');
    }
}
