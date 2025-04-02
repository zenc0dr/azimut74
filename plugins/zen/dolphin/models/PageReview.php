<?php namespace Zen\Dolphin\Models;

use Model;

/**
 * Model
 */
class PageReview extends Model
{
    use \October\Rain\Database\Traits\Validation;
    public $timestamps = false;
    public $table = 'zen_dolphin_page_reviews';
    public $rules = [
    ];

    public function setDataAttribute($value)
    {
        $this->attributes['data'] = json_encode($value, 256);
    }

    public function getDataAttribute($value)
    {
        return json_decode($value, true);
    }
}
