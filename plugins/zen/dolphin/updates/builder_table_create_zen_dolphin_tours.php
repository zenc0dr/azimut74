<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;
use Zen\Dolphin\Classes\Core;

class BuilderTableCreateZenDolphinTours extends Migration
{
    public function up()
    {
        Schema::create('zen_dolphin_tours', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->nullable()->comment = 'Название тура';
            $table->integer('type_id')->default(0)->comment = 'Тип тура 0 - Экскурсионный / 1 - Автобусный'; //duration
            $table->integer('operator_id')->default(0)->comment = 'Туроператор'; //duration
            $table->integer('duration')->default(1)->comment = 'Продолжительность в днях (обязательное поле)';
            $table->text('info')->nullable()->comment = 'Важная информация';
            $table->text('info_tech')->nullable()->comment = 'Служебная информация';
            $table->text('anonce')->nullable()->comment = 'Анонс тура';
            $table->text('tour_program')->nullable()->comment = 'Программа тура (json)';
            $table->text('conditions')->nullable()->comment = 'Условия по туру (json)';
            $table->text('waybill')->nullable()->comment = 'Маршрут';
            $table->text('youtube_link')->nullable()->comment = 'Ссылка на видео';
            $table->integer('active')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        $extra_fields = [
            'type_id' => 1
        ];

        Core::fromList('zen_dolphin_tours', $extra_fields);

    }


    public function down()
    {
        Schema::dropIfExists('zen_dolphin_tours');
    }
}