<?php namespace Zen\Dolphin\Models;

use Model;
use October\Rain\Database\Traits\Sortable;

/**
 * Model
 */
class Azpansion extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use Sortable;

    public $timestamps = false;
    public $table = 'zen_dolphin_azpansions';
    public $rules = [
    ];
}
