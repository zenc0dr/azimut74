<?php namespace Zen\Dolphin\Models;

use Model;
use October\Rain\Database\Traits\Sortable;

/**
 * Model
 */
class PageBlock extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use Sortable;

    public $timestamps = false;
    public $table = 'zen_dolphin_page_blocks';

    public $rules = [
    ];

    public $hasMany = [
        'items' => [
            Page::class,
            'key' => 'pageblock_id'
        ],
    ];

    function getTypeIdOptions()
    {
        return [
            0 => 'Экскурсионные туры',
            1 => 'Автобусные туры'
        ];
    }

    function getTypeIdAttribute($type_id)
    {
        return $type_id ?? 0;
    }

    function getScopeAttribute()
    {
        return ($this->type_id) ? 'АТМ' : 'ЭКСТ';
    }

    function getOptionsAttribute($value)
    {
        return json_decode($value, true);
    }

    function setOptionsAttribute($value)
    {
        $this->attributes['options'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    function getOptionsOptions()
    {
        return [
            'block' => 'Блок свёрнут',
            'items' => 'Подпункты свёрнуты',
            'extra' => 'Лишние пункты скрыты'
        ];
    }

}
