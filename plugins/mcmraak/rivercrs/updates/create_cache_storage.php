<?php namespace Mcmraak\Rivercrs\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateCacheStorage extends Migration
{
    public function up()
    {
       $dir_name = base_path().'/storage/rivercrs_cache';
       if(!file_exists($dir_name)) {
           mkdir($dir_name, 0777, true);
       }
    }

    public function down()
    {
        $dir_name = base_path().'/storage/rivercrs_cache';
        //unlink($dir_name);
    }
}