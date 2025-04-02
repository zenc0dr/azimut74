<?php namespace Mcmraak\Sans\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateCacheStorage extends Migration
{
    public function up()
    {
        $dir_name = base_path().'/storage/sans_cache';
        if(!file_exists($dir_name)) {
            mkdir($dir_name, 0777, true);
        }
        $dir_name = base_path().'/storage/sans_cache/hotels';
        if(!file_exists($dir_name)) {
            mkdir($dir_name, 0777, true);
        }
        $dir_name = base_path().'/storage/sans_cache/queries';
        if(!file_exists($dir_name)) {
            mkdir($dir_name, 0777, true);
        }
    }

    public function down()
    {
        #$dir_name = base_path().'/storage/sans_cache';
        #unlink($dir_name);
    }
}