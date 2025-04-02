<?php namespace Zen\Om\Models;

use Model;

/**
 * Model
 */
class Field extends Model
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
    public $table = 'zen_om_fields';

    public $belongsToMany = [
        'items' => [
            'Zen\Om\Models\Item',
            'table'    => 'zen_om_fields_pivot',
            'key'      => 'field_id',
            'otherKey' => 'item_id'
        ]
    ];

    public function afterDelete()
    {
        FieldPivot::where('field_id', $this->id)->delete();
    }

}