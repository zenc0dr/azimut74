<?php namespace Zen\Dolphin\Models;

use Backend\Facades\BackendAuth;
use Model;
use Zen\Dolphin\Classes\Core;
use Input;
use Log;

/**
 * Model
 */
class City extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Sortable;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'zen_dolphin_cities';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    public $attachMany = [
        'snippets' => [
            'System\Models\File',
            'order' => 'sort_order',
            'delete' => true
        ],
    ];

    public $belongsTo = [
        'region' => [
            Region::class,
            'order' => 'sort_order'
        ],
        'country' => [
            Country::class,
            'order' => 'sort_order'
        ]
    ];

    public function scopeFilterCauntries($query, $model)
    {
        return $query->whereHas('country', function($q) use ($model) {
            $q->whereIn('id', $model);
        });
    }

    public function scopeFilterRegions($query, $model)
    {
        return $query->whereHas('region', function($q) use ($model) {
            $q->whereIn('id', $model);
        });
    }

    function getCountryIdOptions()
    {
        return Country::lists('name', 'id');
    }

    function getRegionIdOptions()
    {
        $country_id = post('City.country_id');

        return Region::where(function($query) use ($country_id) {
            if($country_id) $query->where('country_id', $country_id);
        })->lists('name', 'id');
    }

    function getThumbsAttribute($value)
    {
        if(!$value) return;
        return json_decode($value, true);
    }

    function beforeSave()
    {
        if(!$this->created_by) {
            $this->attributes['created_by'] = 'local';
        }
    }

    function afterSave()
    {
        $core = new Core;
        $core->clearCacheItem('dolphin.service', 'dolphin.GeoTree');
        $core->createSnippets($this);
    }
}
