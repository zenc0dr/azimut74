<?php namespace Mcmraak\Rivercrs\Classes;
# DB Helpers

use DB;

class Dbh
{
    # Функция уничтожающая дубликаты в сводных таблицах
    public static function removePivotDublicates($table){
        $records = DB::table($table)->get()->toArray();
        $search_arr = [];
        foreach ($records as $record) {
            $search_arr[] = json_encode ((array)$record, JSON_UNESCAPED_UNICODE);
        }

        $unique = array_unique($search_arr);

        $count_search_arr = count($search_arr);
        $count_unique = count($unique);

        if($count_search_arr == $count_unique) return;

        $diff = [];
        for($i=0;$i<$count_search_arr;$i++)
        {
            if(!isset($unique[$i])) {
                $diff[] = $search_arr[$i];
            }
        }

        $diff = array_unique($diff);
        $diff = array_values($diff);

        foreach ($diff as $item) {
            $item = json_decode($item,1);
            $real = DB::table($table)->where($item)->first();
            DB::table($table)->where($item)->delete();
            DB::table($table)->insert((array)$real);
        }
    }
}