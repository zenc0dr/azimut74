<?php namespace Zen\Dolphin\Models;

use Model;
use October\Rain\Database\Traits\Sortable;

/**
 * Model
 */
class PageGroup extends Model
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
    public $table = 'zen_dolphin_page_groups';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    public $attachOne = [
        'image' => [
            'System\Models\File',
            'delete' => true
        ],
    ];

    public $belongsToMany = [
        'pages' => [
            'Zen\Dolphin\Models\Page', # Другая модель с которой мы устанавливаем связь через сводную таблицу
            'table'    => 'zen_dolphin_page_group_pivot', # Сводная таблица
            'key'      => 'group_id', # Ключ в сводной таблице отображающий id этой модели
            'otherKey' => 'page_id', # Ключ в сводной таблице отображающий id  другой модели
        ]
    ];

    public function getScopeOptions()
    {
        return [
            'ext' => 'Экскурсионные туры',
            'atm' => 'Автобусные туры'
        ];
    }

    public function getScopeAttribute($scope_code)
    {
        return $scope_code ?? 'ext';
    }
}
