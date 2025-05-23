<?php namespace Mcmraak\Sans\Models;

use Model;

/**
 * Model
 */
class Meal extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'mcmraak_sans_meals';
    protected $fillable = [
        'id',
        'name',
        'code',
    ];
}
