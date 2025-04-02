<?php namespace Srw\Catalog\Models;

use Model;

/**
 * Model
 */
class Tops extends Model
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
    public $table = 'srw_catalog_tops';

    /* Relationships */
    public $belongsTo = [
        'items' => [
            'Srw\Catalog\Models\Items',
            'key' => 'item_id'
        ]
    ];

}