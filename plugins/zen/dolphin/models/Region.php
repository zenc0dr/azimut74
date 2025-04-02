<?php namespace Zen\Dolphin\Models;

use Backend\Facades\BackendAuth;
use Model;
use Cache;
use Zen\Dolphin\Classes\Core;

/**
 * Model
 */
class Region extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Sortable;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'zen_dolphin_regions';

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
        'country' => [
            'Zen\Dolphin\Models\Country',
            'order' => 'sort_order',
            'key' => 'country_id'
        ]
    ];

    public $hasMany = [
        'cities' => [
            'Zen\Dolphin\Models\City',
            'key' => 'region_id'
        ],
    ];

    public function scopeFilterCauntries($query, $model)
    {
        return $query->whereHas('country', function($q) use ($model) {
            $q->whereIn('id', $model);
        });
    }

    function getCountryIdOptions()
    {
        return Country::lists('name', 'id');
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
