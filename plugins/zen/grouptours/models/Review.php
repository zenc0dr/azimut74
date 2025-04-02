<?php namespace Zen\GroupTours\Models;

use Model;

/**
 * Model
 */
class Review extends Model
{
    use \October\Rain\Database\Traits\Validation;

    public $timestamps = false;
    public $table = 'zen_grouptours_reviews';

    public $rules = [
        'name' => 'required|unique:zen_grouptours_reviews',
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
