<?php namespace Mcmraak\Rivercrs\Models;

use Model;
use October\Rain\Exception\ValidationException;
use Log;
use DB;
use Mcmraak\Rivercrs\Models\Backup;
use Mcmraak\Rivercrs\Classes\Dbh;
use Mcmraak\Rivercrs\Classes\CacheSettings;
use Cache;

/**
 * Model
 */
class Towns extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    /*
     * Validation
     */
    public $rules = [
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'mcmraak_rivercrs_towns';

    public $belongsTo = [
        'soft' => [
            'Mcmraak\Rivercrs\Models\Towns',
            'key' => 'soft_id'
        ],
    ];

    public $hasMany = [
        'soft_items' => [
            'Mcmraak\Rivercrs\Models\Towns',
            'key' => 'soft_id'
        ],
    ];

    # for filter by eds_code
    public function scopeFilterEds($query, $text)
    {
        $text = CacheSettings::edsNameToCode($text);
        return $query->where('eds_code', $text);
    }

    # Названия источников из настроек
    public function getEdsNameAttribute()
    {
        return CacheSettings::edsCodeToName($this->eds_code);
    }

    public function beforeDelete()
    {
        $deleteItems = Waybills::where('town_id', $this->id)->get();
        if(count($deleteItems))
        foreach ($deleteItems as $item)
        {
            $checkin_id = $item->checkin_id;
            $count = Waybills::where('checkin_id', $checkin_id)->count();
            //throw new ValidationException(['$count'.$count]);
            if($count <= 2)
            {
                throw new ValidationException(
                    ['Нельзя удалить город, так как он участвует
                     в построении маршрута с двумя (или менее) контрольными точками!
                     Подобный маршрут находится в заезде id#'.$checkin_id]
                    );
            }
        }
    }

    public function getSoftIdOptions()
    {
        return [0 => ' -- '] + self::where('id', '<>', $this->id)->lists('name', 'id');
    }

    public function getHardIdOptions()
    {
        return [0 => ' -- '] + self::where('id', '<>', $this->id)->lists('name', 'id');
    }

    public function afterSave()
    {
        Cache::forget('rivercrs.FilterDATA');
    }

    public function afterDelete()
    {
        Waybills::where('town_id', $this->id)->delete();
    }


}
