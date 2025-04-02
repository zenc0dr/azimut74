<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenDolphinLog extends Migration
{
    public function up()
    {
        Schema::create('zen_dolphin_log', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->text('text')->nullable()->comment = 'Текст лога';
            $table->string('type')->nullable()->comment = 'Тип лога (info, error)';
            $table->string('source')->nullable()->comment = 'Источник лога (info, error)';

            # TODO: Времеено отменено, не всегда читается
            # $table->binary('dump')->nullable()->comment = 'Дамп в JSON архивированный в GZ';

            $table->longText('dump')->nullable()->comment = 'Дамп в JSON';
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('zen_dolphin_log');
    }
}
