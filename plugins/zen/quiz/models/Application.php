<?php namespace Zen\Quiz\Models;

use Model;

/**
 * Model
 */
class Application extends Model
{
    use \October\Rain\Database\Traits\Validation;


    /**
     * @var string The database table used by the model.
     */
    public $table = 'zen_quiz_applications';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    public $belongsTo = [
        'city' => [
            City::class,
            'order' => 'sort_order'
        ],
    ];

    function getCityIdOptions()
    {
        return City::lists('name', 'id');
    }
}
