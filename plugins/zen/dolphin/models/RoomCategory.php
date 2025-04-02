<?php namespace Zen\Dolphin\Models;

use Model;

/**
 * Model
 */
class RoomCategory extends Model
{
    use \October\Rain\Database\Traits\Validation;
    

    /**
     * @var string The database table used by the model.
     */
    public $table = 'zen_dolphin_roomsc';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];
}
