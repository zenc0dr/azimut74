<?php namespace Mcmraak\Rivercrs\Models;

use Model;
use October\Rain\Database\Traits\Sortable;

/**
 * Model
 */
class Ktype extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use Sortable;

    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    public $hasMany = [
        'pages' => [Kpage::class, 'order' => 'sort_order'],
    ];


    /**
     * @var string The database table used by the model.
     */
    public $table = 'mcmraak_rivercrs_ktypes';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];
}
