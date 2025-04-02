<?php namespace Mcmraak\Rivercrs\Classes;

use Log;
use Mcmraak\Rivercrs\Classes\CacheSettings;

class Idmemory
{
    public static function test($db_name, $id, $time=null)
    {
        $db_change = false;
        $id_exist = false;
        $path = base_path().'/storage/cms/cache/'.$db_name.'.ids';

        if(!file_exists($path)) {
            $db = [];
            $db[$id] = time();
            $db = serialize($db);
            file_put_contents($path, $db);
            return false;
        }

        $db = file_get_contents($path);
        $db = unserialize($db);

        if(isset($db[$id])) {
            if($time) {
                $diff = time() - $db[$id];
                if($diff < $time) {
                    $id_exist = true;
                } else {
                    $db_change = true;
                }
            } else {
                $id_exist = true;
            }
        } else {
            $db_change = true;
        }

        if($db_change) {
            $db[$id] = time();
            $db = serialize($db);
            file_put_contents($path, $db);
        }

        return $id_exist;
    }

    public static function loadArray($db_name)
    {
        $path = base_path().'/storage/cms/cache/'.$db_name.'.ids';


        if(!file_exists($path)) return [];
        $db = file_get_contents($path);
        return unserialize($db);
    }

    public static function idsCount()
    {
        $eds_codes = CacheSettings::getEsdCodes();
        $count = 0;
        foreach ($eds_codes as $code) {
            if($code == 'infoflot') continue;
            $count += count(self::loadArray($code));
        }

        # Infoflot ids
        $count += count((new \Mcmraak\Rivercrs\Classes\Ids('infoflot_cache'))->like('cruise_seed:'));

        return $count;
    }

    public static function isExist($eds_code, $eds_id)
    {
        $ids = self::loadArray($eds_code);
        foreach ($ids as $id => $time) {
            if($id == $eds_id) return true;
        }
        return false;
    }

    # @return (string) ~ '08.10.2018 13:58'
    public static function lastTime($db_name)
    {
        $db = self::loadArray($db_name);
        if(!$db) return false;
        end($db);
        $key = key($db);
        $time = $db[$key];
        return date('d.m.Y H:i', $time);
    }
}