<?php namespace Mcmraak\Rivercrs\Models;

use Model;
use Mcmraak\Rivercrs\Models\Motorships;

/**
 * Model
 */
class Transit extends Model
{
    use \October\Rain\Database\Traits\Validation;
    //use \October\Rain\Database\Traits\Sortable;
    
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
    public $table = 'mcmraak_rivercrs_transit';

    /* Relations */

    public $attachMany = [
        'images' => ['System\Models\File', 'order' => 'sort_order', 'delete' => true],
    ];

    public $belongsTo = [
        'cruise' => [
            'Mcmraak\Rivercrs\Models\Cruises',
            'key' => 'parent_id',
            'order' => 'sort_order',
        ],
    ];

    # for filter of motorshis in cabins field list
    public function scopeFilterCruises($query, $find)
    {
        return $query->whereHas('cruise', function($q) use ($find) {

            $q->whereIn('id', $find);
        });
    }


    public function getTown1Options()
    {
        return [0 => 'Не выбрано'] + Towns::lists('name', 'id');
    }
    public function getTown2Options()
    {
        return [0 => 'Не выбрано'] + Towns::lists('name', 'id');
    }

    public function getSeoArticlesAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setSeoArticlesAttribute($value)
    {
        $this->attributes['seo_articles'] = json_encode ($value, JSON_UNESCAPED_UNICODE);
    }

    public function getShipIdOptions()
    {
        return [0 => ' -- '] + Motorships::lists('name', 'id');
    }
}