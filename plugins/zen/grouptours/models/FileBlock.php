<?php namespace Zen\GroupTours\Models;

use Model;

/**
 * Model
 */
class FileBlock extends Model
{
    use \October\Rain\Database\Traits\Validation;
    public $timestamps = false;
    public $table = 'zen_grouptours_files';
    public $rules = [
    ];

    public $attachMany = [
        'files' => [
            'System\Models\File',
            'order' => 'sort_order',
            'delete' => true
        ],
    ];
}
