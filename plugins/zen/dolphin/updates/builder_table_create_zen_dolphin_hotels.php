<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;
use Zen\Dolphin\Classes\Core;

class BuilderTableCreateZenDolphinHotels extends Migration
{
    public function up()
    {
        Schema::create('zen_dolphin_hotels', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name')->nullable();

            # Отношения
            $table->integer('operator_id')->default(0);

            # География
            $table->integer('country_id')->default(0);
            $table->integer('region_id')->default(0);
            $table->integer('city_id')->default(0);

            # Характеристики
            $table->integer('distinct')->default(0);
            $table->integer('type_id')->default(0);
            $table->integer('stars')->default(0);
            $table->integer('to_sea')->default(0)->comment = 'Расстояние до моря';
            $table->time('checkout_time')->nullable();
            $table->time('checkin_time')->nullable();

            # Информация
            $table->text('info_tech')->nullable()->comment = 'Служебная информация';
            $table->text('address')->nullable();
            $table->text('contacts')->nullable();
            $table->text('info')->nullable();
            $table->text('ci')->nullable()->comment = 'ConstructionInfo';
            $table->text('ta')->nullable()->comment = 'TransportAccessability';
            $table->string('gps')->nullable()->comment = 'Longitude:Latitude';
            $table->text('gallery')->nullable()->comment = 'Внешние изображения';

            # Мультивыборы в json

            # Дополнительная информация
            $table->longText('additional')->nullable()->comment = 'Дополнительная информация';

            # Метаданные
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->integer('sort_order')->default(0);

            # Авторство
            $table->string('created_by')->default('local');
            $table->integer('eid')->default(0);
        });

        $extra_fields = [
            'created_by' => 'azimut'
        ];

        Core::fromList('zen_dolphin_hotels', $extra_fields);
    }

    public function down()
    {
        Schema::dropIfExists('zen_dolphin_hotels');
    }
}
