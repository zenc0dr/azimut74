<?php namespace Zen\Forms\Models;

use Model;

/**
 * Model
 */
class Item extends Model
{
    use \October\Rain\Database\Traits\Validation;


    /**
     * @var string The database table used by the model.
     */
    public $table = 'zen_forms_items';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    protected $fillable = [
        'data',
        'code',
        'status'
    ];
}
