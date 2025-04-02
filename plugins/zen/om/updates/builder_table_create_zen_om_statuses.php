<?php namespace Zen\Om\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenOmStatuses extends Migration
{
    public function up()
    {
        Schema::create('zen_om_statuses', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->nullable();
            $table->text('desc')->nullable();
            $table->integer('sort_order')->default(0);
        });

        \Zen\Om\Models\Status::insert(
            [
                [
                    'id' => 1,
                    'name' => 'Created' # Заказ создан
                ],
                [
                    'id' => 2,
                    'name' => 'Paid' # Заказ оплачен
                ],
                [
                    'id' => 3,
                    'name' => 'Processed' # Заказ обработан
                ],
                [
                    'id' => 4,
                    'name' => 'Closed' # Заказ закрыт (получен)
                ],
            ]
        );
    }
    
    public function down()
    {
        Schema::dropIfExists('zen_om_statuses');
    }
}