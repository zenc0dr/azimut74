<?php namespace Zen\Reviews\Models;

use Model;

/**
 * Model
 */
class Email extends Model
{
    use \October\Rain\Database\Traits\Validation;


    /**
     * @var string The database table used by the model.
     */
    public $table = 'zen_reviews_emails';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    protected $fillable = [
        'email'
    ];
}
