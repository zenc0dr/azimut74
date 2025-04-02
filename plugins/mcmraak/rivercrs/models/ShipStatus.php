<?php namespace Mcmraak\Rivercrs\Models;

use Model;

/**
 * Model
 */
class ShipStatus extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;


    /**
     * @var string The database table used by the model.
     */
    public $table = 'mcmraak_rivercrs_ship_statuses';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];
}
