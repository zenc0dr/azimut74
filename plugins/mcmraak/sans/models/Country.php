<?php namespace Mcmraak\Sans\Models;

use Model;

/**
 * Model
 */
class Country extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    /*
     * Validation
     */
    public $rules = [
    ];

    protected $fillable = [
        'id',
        'cid',
        'name',
    ];


    /**
     * @var string The database table used by the model.
     */
    public $table = 'mcmraak_sans_countries';
}