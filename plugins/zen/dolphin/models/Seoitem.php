<?php namespace Zen\Dolphin\Models;

use Model;
use October\Rain\Database\Traits\Sortable;

/**
 * Model
 */
class Seoitem extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use Sortable;

    public $timestamps = false;
    public $table = 'zen_dolphin_seoitems';
    public $rules = [
    ];

    public $belongsTo = [
        'page' => [
            Page::class,
            'key' => 'page_id'
        ],
    ];

    function getPageIdOptions()
    {
        return Page::lists('name', 'id');
    }

    function getNameAttribute($value)
    {
        if(!$this->id) return;
        return ($value) ? $value : $this->page->label;
    }

    function getScopeAttribute()
    {
        $type_id = $this->page->page_block->type_id;
        return ($type_id) ? 'АТМ' : 'ЭКСТ';
    }
}
