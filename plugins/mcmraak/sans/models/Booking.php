<?php namespace Mcmraak\Sans\Models;

use Model;

/**
 * Model
 */
class Booking extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /**
     * @var array Validation rules
     */

   
    
    public $rules = [
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'mcmraak_sans_booking';

    public function getDataAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setDataAttribute($value)
    {
        $this->attributes['data'] = json_encode ($value, JSON_UNESCAPED_UNICODE);
    }
}
