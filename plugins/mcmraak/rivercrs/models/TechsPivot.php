<?php namespace Mcmraak\Rivercrs\Models;

use Model;

/**
 * Model
 */
class TechsPivot extends Model
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

    /**
     * @var string The database table used by the model.
     */
    public $table = 'mcmraak_rivercrs_techs_pivot';

//    public $belongsTo = [
//        'names' => [
//            'Mcmraak\Rivercrs\Models\Techs',
//            'key' => 'tech_id'
//        ],
//    ];

}