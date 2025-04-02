<?php namespace Zen\Keeper\Models;

use Model;

/**
 * Model
 */
class Site extends Model
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
    public $table = 'zen_keeper_sites';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    function setUrlAttribute($value)
    {
        if(!$value) return;
        $this->attributes['url'] = preg_replace("/\/$/", '', $value);
    }

}
