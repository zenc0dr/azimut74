<?php namespace Zen\Dolphin\Models;

use Model;

/**
 * Model
 * @method static get()
 * @method static orderBy(string $string)
 */
class Review extends Model
{
    use \October\Rain\Database\Traits\Validation;

    public $timestamps = false;
    public $table = 'zen_dolphin_reviews';
    protected $dates = ['date_time'];

    public $rules = [];

    public $attachOne = [
        'image' => [
            'System\Models\File',
            'delete' => true
        ],
        'avatar' => [
            'System\Models\File',
            'delete' => true
        ],
    ];

    public $casts = [
        'stars' => 'integer'
    ];

}
