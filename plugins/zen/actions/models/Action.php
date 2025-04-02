<?php namespace Zen\Actions\Models;

use Model;
use Zen\Actions\Classes\Core;

/**
 * Model
 */
class Action extends Model
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
    public $table = 'zen_actions_items';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    function setUseArrayAttribute($value) {
        $this->attributes['use'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    function getUseArrayAttribute()
    {
        return json_decode($this->use, true);
    }

    function setInputsArrayAttribute($value) {
        $this->attributes['inputs'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    function getInputsArrayAttribute()
    {
        return json_decode($this->inputs, true);
    }
}
