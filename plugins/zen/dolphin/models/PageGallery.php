<?php namespace Zen\Dolphin\Models;

use Model;

/**
 * Model
 */
class PageGallery extends Model
{
    use \October\Rain\Database\Traits\Validation;
    public $timestamps = false;
    public $table = 'zen_dolphin_page_galleries';
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
