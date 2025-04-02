<?php namespace Zen\Dolphin\Models;

use Model;

/**
 * Model
 */
class Faq extends Model
{
    use \October\Rain\Database\Traits\Validation;
    public $timestamps = false;
    public $table = 'zen_dolphin_faq';
    public $rules = [];

    public function setDataAttribute($value)
    {
        $this->attributes['data'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public function getDataAttribute($value)
    {
        return json_decode($value, true);
    }
}
