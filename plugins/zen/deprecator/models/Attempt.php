<?php namespace Zen\Deprecator\Models;

use Model;

/**
 * Model
 */
class Attempt extends Model
{
    use \October\Rain\Database\Traits\Validation;

    public $timestamps = false;
    public $incrementing = false;
    public $keyType = 'string';
    public $primaryKey = 'signature';
    public $table = 'zen_deprecator_attempts';
    public $rules = [];

    protected $fillable = [
        'signature',
        'time',
    ];

    protected $dates = [
        'time'
    ];
}
