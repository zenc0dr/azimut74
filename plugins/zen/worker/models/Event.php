<?php namespace Zen\Worker\Models;

use Model;
use Zen\Worker\Classes\Core;

/**
 * Model
 */
class Event extends Model
{
    use \October\Rain\Database\Traits\Validation;

    public $table = 'zen_worker_events';
    public $rules = [];

    function getDataAttribute($value)
    {
        return json_decode($value, true);
    }

    function setDataAttribute($value)
    {
        $this->attributes['data'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    function getDataDumpAttribute()
    {
        return Core::dumper($this->data);
    }
}
