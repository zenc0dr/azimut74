<?php namespace Zen\GroupTours\Models;

use Model;

/**
 * Model
 */
class Feature extends Model
{
    use \October\Rain\Database\Traits\Validation;

    public $timestamps = false;
    public $table = 'zen_grouptours_features';
    public $rules = [
    ];

    public $attachMany = [
        'images' => [
            'System\Models\File',
            'order' => 'sort_order',
            'delete' => true
        ],
    ];
}
