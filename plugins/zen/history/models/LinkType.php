<?php namespace Zen\History\Models;

use Model;

/**
 * Model
 */
class LinkType extends Model
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
    public $table = 'zen_history_link_types';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];
}
