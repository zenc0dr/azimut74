<?php namespace Zen\Worker\Models;

use Model;

/**
 * Model
 */
class Job extends Model
{
    use \October\Rain\Database\Traits\Validation;

    public $table = 'zen_worker_jobs';
    public $rules = [];

    function getDataAttribute($value)
    {
        return json_decode($value, true);
    }

    function setDataAttribute($value)
    {
        $this->attributes['data'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    function getDataStringAttribute()
    {
        return $this->getOriginal('data');
    }
}
