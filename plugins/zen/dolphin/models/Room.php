<?php namespace Zen\Dolphin\Models;

use BackendAuth;
use Model;

/**
 * Model
 */
class Room extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Sortable;

    public $table = 'zen_dolphin_rooms';
    public $rules = [
    ];
}
