<?php namespace Zen\Worker\Models;

use Carbon\Carbon;
use Model;

/**
 * Model
 */
class ErrorLog extends Model
{
    use \October\Rain\Database\Traits\Validation;

    public $table = 'zen_worker_errors';
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

    static function rotate()
    {
        self::where('created_at', '<', Carbon::now()->subMonth()->toDateString())->delete();
    }
}
