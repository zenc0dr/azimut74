<?php namespace Zen\Dolphin\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;
use Zen\Cabox\Models\Storage;

class DolphinInit extends Migration
{
    public function up()
    {
        Storage::init([
            'code' => 'dolphin.parsers',
            'name' => 'Дельфин - HTTP',
            'path' => ':storage/dolphin_cache/parsers',
            'time' => 0,
            'one_folder' => 1,
            'compress' => 1,
            'images' => 0,
        ]);

        Storage::init([
            'code' => 'dolphin.search',
            'name' => 'Дельфин - Потоки',
            'path' => ':storage/dolphin_cache/search',
            'time' => 0,
            'one_folder' => 1,
            'compress' => 1,
            'images' => 0,
        ]);

        Storage::init([
            'code' => 'dolphin.service',
            'name' => 'Дельфин - Сервисный кэш',
            'path' => ':storage/dolphin_cache/service',
            'time' => 0,
            'one_folder' => 1,
            'compress' => 1,
            'images' => 0,
        ]);
    }

    public function down()
    {

    }
}
