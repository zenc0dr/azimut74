<?php namespace Zen\Om\Models;

use Model;

/**
 * Model
 */
class Brand extends Model
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
        'slug' => 'required|unique:zen_om_brands'
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'zen_om_brands';

    public $attachMany = [
        'images' => ['System\Models\File', 'order' => 'sort_order', 'delete' => true],
    ];

    public function afterDelete()
    {
        Item::where('brand_id', $this->id)->update(['brand_id'=>0]);
    }
}