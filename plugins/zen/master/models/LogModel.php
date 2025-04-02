<?php namespace Zen\Master\Models;

use Model;

/**
 * Model
 */
class LogModel extends Model
{
    use \October\Rain\Database\Traits\Validation;

    public $table = 'zen_master_log';

    public $rules = [
    ];

    protected $fillable = [
        'event_name',
        'data',
        'ip'
    ];
}
