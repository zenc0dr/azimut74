<?php namespace Zen\Dolphin\Models;

use Model;
use Zen\Dolphin\Models\Country;
use Zen\Dolphin\Models\Region;
use Zen\Cabox\Classes\Cabox;

/**
 * Model
 */
class Extour extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;


    /**
     * @var string The database table used by the model.
     */
    public $table = 'zen_dolphin_extours';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    function getGeoObjectOptions()
    {
        $list = [];
        $countries = Country::get();
        foreach ($countries as $country) {
            $list['1:' . $country->id] = 'Страна: ' . $country->name;
            $regions = $country->regions;
            foreach ($regions as $region) {
                $list['2:' . $region->id] = ' - Регион: ' . $region->name;
            }
        }
        return $list;

    }

    function setGeoObjectAttribute($value)
    {
        $e = explode(':', $value);
        $model = $e[0];
        if ($model == 1) $model = 'Zen\Dolphin\Models\Country';
        if ($model == 2) $model = 'Zen\Dolphin\Models\Region';
        $this->attributes['geo_model'] = $model;
        $this->attributes['geo_id'] = $e[1];
    }

    function getExtoursEidsArrAttribute()
    {
        return json_decode($this->extours_eids, 1);
    }

    public static function getExtoursTable(): array
    {
        $extours = self::get();
        if (!$extours) {
            return [];
        }
        $return = [];
        foreach ($extours as $extour) {
            $level = null;
            if ($extour->geo_model == 'Zen\Dolphin\Models\Country') {
                $level = 0;
            }
            if ($extour->geo_model == 'Zen\Dolphin\Models\Region') {
                $level = 1;
            }
            $id_level = $level + 1;
            $return["$level:$extour->geo_id"][] = [
                'id' => "$id_level:ex{$extour->id}",
                'name' => $extour->name,
            ];
        }
        return $return;
    }

    function afterSave()
    {
        (new Cabox('dolphin.service'))->del('dolphin.GeoTree');
    }

}
