<?php namespace Zen\Dolphin\Models;

use Model;

/**
 * Model
 */
class Folder extends Model
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
    public $table = 'zen_dolphin_folders';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    public $belongsToMany = [
        'blocks' => [
            'Zen\Dolphin\Models\Block',
            'table'    => 'zen_dolphin_blocks_folders_pivot',
            'key'      => 'folder_id',
            'otherKey' => 'block_id',
        ],
    ];

}
