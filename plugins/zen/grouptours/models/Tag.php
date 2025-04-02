<?php namespace Zen\GroupTours\Models;

use Model;
use October\Rain\Database\Traits\Sortable;

/**
 * Model
 */
class Tag extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use Sortable;
    public $timestamps = false;
    public $table = 'zen_grouptours_tags';
    public $rules = [
        'name' => 'required|unique:zen_grouptours_tags',
    ];
}
