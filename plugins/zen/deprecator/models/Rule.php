<?php namespace Zen\Deprecator\Models;

use Model;

/**
 * Model
 */
class Rule extends Model
{
    use \October\Rain\Database\Traits\Validation;

    public $timestamps = false;
    public $table = 'zen_deprecator_rules';
    protected $fillable = [
        'name',
        'code',
        'ttl',
    ];
    public $rules = [];
}
