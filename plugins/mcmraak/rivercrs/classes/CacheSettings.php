<?php namespace Mcmraak\Rivercrs\Classes;

use Config;
use October\Rain\Database\Model;
use October\Rain\Database\Traits\Validation as ValidationTrait;
use Mcmraak\Rivercrs\Models\Motorships as Ship;
use Cache;
use File;

class CacheSettings extends Model
{
    use ValidationTrait;

    public $implement = [
        'System.Behaviors.SettingsModel',
    ];

    public $settingsCode = 'mcmraak_rivercrs_cachesettings';

    public $settingsFields = 'fields.yaml';

    public $rules = [
    ];

    public static function edsCodeToName($code)
    {
        $eds_names = CacheSettings::get('eds_names');
        foreach ($eds_names as $item)
        {
            if($item['eds_code'] == $code) {
                return $item['eds_name'];
            }
        }
        return $code;
    }

    public static function edsNameToCode($name)
    {
        $eds_names = CacheSettings::get('eds_names');
        foreach ($eds_names as $item)
        {
            if($item['eds_name'] == $name) {
                return $item['eds_code'];
            }
        }
        return $name;
    }

    function getEdsIdOptions()
    {
        return [
            'volga' => 'ВолгаWolga',
            'germes' => 'Спутник-Гермес',
            'waterway' => 'Водоходъ',
            'gama' => 'Гама',
            'infoflot' => 'Инфофлот'
        ];
    }

    function getNotLetMotorshipsAttribute($value)
    {
        #$value = self::get('not_let_motorships');
        $return = [];
        foreach ($value as $edsData) {
            $ship_names = $edsData['name'];
            $ship_names = explode("\n", $ship_names);
            $i=0;
            foreach ($ship_names as $ship) {
                $ship_names[$i] = trim($ship);
                $i++;
            }
            $ship_names = array_unique($ship_names);
            sort($ship_names);
            $ship_names = join("\n", $ship_names);
            $return[] = [
                'eds' => $edsData['eds'],
                'name' => $ship_names
            ];
        }
        return $return;
    }

    # bad_ships - Возвращает либо массив с именами по указанию кода eds 'infoflot'
    # Либо возвращает в анонимную функцию массив вида [0 => [eds => 'xxx', 'name' => [...]]]
    static function getBadShipsSettings($esd_code=null) {
        $settings = self::get('not_let_motorships');

        if(is_callable($esd_code)) {
            $return = [];
            foreach ($settings as $setting) {
                $ships = explode("\n", $setting['name']);
                $ships_arr = [];
                foreach ($ships as $ship) {
                    $ships_arr[] = trim($ship);
                }

                $return[] = [
                    'eds' => $setting['eds'],
                    'name' => $ships_arr
                ];
            }
            return $esd_code($return);
        }

        $return = [];
        foreach ($settings as $setting) {
            $eds = $setting['eds'];
            $ships = explode("\n", $setting['name']);
            foreach ($ships as $ship) {
                if($esd_code && $eds == $esd_code) {
                    $return[] = trim($ship);
                }
                if(!$esd_code) {
                    $return[] = trim($ship);
                }
            }
        }

        return $return;
    }

    # Возвращает ...
    # $method = 'names:infoflot';
    static function getBadShips($method='')
    {
        if (Cache::has('getExcludedShips'.$method)) {
            return Cache::get('getExcludedShips'.$method);
        }

        # Все коды eds
        # $eds_codes = collect(CacheSettings::get('eds_names'))->pluck('eds_code')->toArray();

        if(strpos($method, 'names:') !== false) {
            $edsCode = explode(':', $method)[1];
            $names = CacheSettings::getBadShipsSettings(function($settings) use ($edsCode) {
                foreach ($settings as $setting) {
                    if($setting['eds'] == $edsCode) {
                        return $setting['name'];
                    }
                }
            });
            Cache::add('getExcludedShips'.$method, $names, 120); # 2 часа
            return $names;
        }

        $skipped_eds = CacheSettings::getBadShipsSettings(function($settings){
            $reverce = [];
            foreach ($settings as $edsData) {
                foreach ($edsData['name'] as $name) {
                    $reverce[$name][$edsData['eds']] = null;
                }
            }
            $return = [];
            foreach ($reverce as $key => $codes) {
                $return[] = [
                    'name' => $key,
                    'esd_codes' => array_keys($codes)
                ];
            }
            return $return;
        });

        $skipped_ships = Ship::where(function ($q) use ($skipped_eds) {
            foreach ($skipped_eds as $eds) {
                $query_arr = [];
                $query_arr[] = ['name', 'like', "%{$eds['name']}%"];
                foreach ($eds['esd_codes'] as $code) {
                    $query_arr[] = [$code.'_id', '<>', 0];
                }
                $q->orWhere($query_arr);
            }
        })->get();

        $return = [];
        foreach ($skipped_ships as $ship) {
            foreach ($skipped_eds as $eds) {
                if(strpos($ship->name, $eds['name']) !== false) {
                    foreach ($eds['esd_codes'] as $code) {
                        $return[] = "{$ship->id}:$code";
                    }
                }
            }
        }

        Cache::add('getExcludedShips'.$method, $return, 120); # 2 часа
        return $return;
    }

    static function shipIsBad($name, $eds_code)
    {
        $eds_code = str_replace('_id', '', $eds_code); # Для совместимости с вызовами из старых методов
        $name = trim($name);
        $lower_name = strtolower($name);
        $bad_ships = self::getBadShips("names:$eds_code");
        if(!$bad_ships) return false;
        foreach ($bad_ships as $bad_ship_name) {
            if(strpos(strtolower($bad_ship_name), $lower_name) !== false) {
                return true;
            }
        }
        return false;
    }

    static function getEsdCodes()
    {
        return collect(CacheSettings::get('eds_names'))->pluck('eds_code')->toArray();
    }

    function afterSave()
    {
        $eds_codes = self::getEsdCodes();
        foreach ($eds_codes as $code) {
            $method = "names:$code";
            Cache::forget('getExcludedShips'.$method);
        }
        Cache::forget('getExcludedShips');
    }
}
