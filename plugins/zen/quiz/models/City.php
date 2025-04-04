<?php namespace Zen\Quiz\Models;

use Model;
use October\Rain\Database\Traits\Sortable;

/**
 * Model
 */
class City extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use Sortable;
    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;


    /**
     * @var string The database table used by the model.
     */
    public $table = 'zen_quiz_cities';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];
}
