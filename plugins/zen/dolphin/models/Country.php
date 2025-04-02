<?php namespace Zen\Dolphin\Models;

use Backend\Facades\BackendAuth;
use Model;
use Cache;
use Zen\Dolphin\Classes\Core;
use Zen\Dolphin\Classes\Helpers;

/**
 * Model
 */
class Country extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Sortable;


    /**
     * @var string The database table used by the model.
     */
    public $table = 'zen_dolphin_countries';

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

    public $hasMany = [
        'regions' => [
            'Zen\Dolphin\Models\Region',
            'key' => 'country_id'
        ],
    ];

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
