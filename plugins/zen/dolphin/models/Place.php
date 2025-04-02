<?php namespace Zen\Dolphin\Models;

use Model;
use Zen\Dolphin\Classes\Core;
use Zen\Dolphin\Models\Country;
use DB;

/**
 * Model
 */
class Place extends Model
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
    public $table = 'zen_dolphin_places';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    function getGeoCodeOptions()
    {
        $countries = Country::get();
        $output = [];
        foreach ($countries as $country) {
            $output['0:'.$country->id] = 'Стр: '.$country->name;
            if($country->regions) {
                foreach ($country->regions as $region) {
                    $output['1:'.$region->id] = '- Рег: '.$region->name;
                    if($region->cities) {
                        foreach ($region->cities as $city) {
                            $output['2:'.$city->id] = '-- Гор: '.$city->name;
                        }
                    }
                }
            }
        }

        return $output;
    }

    function setGeoName()
    {
        $models = [
            'Country',
            'Region',
            'City'
        ];

        $geo_code = $this->geo_code;
        if(!$geo_code) return;

        $geo_code = explode(':', $geo_code);
        $geoObject = app('Zen\Dolphin\Models\\'.$models[$geo_code[0]])->find($geo_code[1]);
        if(!$geoObject) return;

        DB::table($this->table)->where('id', $this->id)->update([
            'geo_name' => $geoObject->name
        ]);

    }

    /**
     * @param array|null $ids - Список идентификаторов мест
     * @return array
     */
    public static function getPlacesTable(array $places_ids = null): array
    {
        if ($places_ids) {
            $places = self::where(function ($query) use ($places_ids) {
                $query->whereIn('id', $places_ids);
            })->orderBy('sort_order')->get();
        } else {
            $places = self::orderBy('sort_order')->get();
        }

        $output = [];
        foreach ($places as $place) {
            $geo_code = explode(':', $place->geo_code);
            $level = $geo_code[0] + 1;
            $output[$place->geo_code][] = [
                'id' => "$level:ps$place->id",
                'name' => $place->name,
            ];
        }
        return $output;
    }

    public function afterSave()
    {
        $this->setGeoName();
        $core = new Core;
        $core->clearCacheItem('dolphin.service', 'dolphin.GeoTree');
    }


}
