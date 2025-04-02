<?php namespace Zen\GroupTours\Models;

use Model;

/**
 * Model
 */
class Order extends Model
{
    use \October\Rain\Database\Traits\Validation;
    public $table = 'zen_grouptours_orders';
    public $rules = [];

    public function getDataAttribute($value)
    {
        if (!$value) {
            return null;
        }

        return json_decode($value, true);
    }

    public function setDataAttribute($value)
    {
        $this->attributes['data'] = json_encode($value, 256);
    }
}
