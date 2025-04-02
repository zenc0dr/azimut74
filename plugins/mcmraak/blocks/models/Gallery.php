<?php namespace Mcmraak\Blocks\Models;

use Model;

/**
 * Model
 */
class Gallery extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    /**
     * @var array Validation rules
     */
    public $rules = [
        'code' => 'required|unique:mcmraak_blocks_galleries'
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'mcmraak_blocks_galleries';

    /* Relations */
    public $attachMany = [
        'images' => [
            'System\Models\File',
            'order' => 'sort_order',
            'delete' => true
        ],
    ];
    public $belongsTo = [
        'style' => [
            'Mcmraak\Blocks\Models\Style',
            'key' => 'style_id'
        ],
    ];
}
