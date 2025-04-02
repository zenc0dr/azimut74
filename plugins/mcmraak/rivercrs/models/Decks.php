<?php namespace Mcmraak\Rivercrs\Models;

use Model;
use Flash;
use DB;
use October\Rain\Exception\ApplicationException;

/**
 * Model
 */
class Decks extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Sortable;
    
    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    /*
     * Validation
     */
    public $rules = [
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'mcmraak_rivercrs_decks';

    // У каждой палубы есть много кают
    public $belongsToMany = [
        'cabins' => [
            'Mcmraak\Rivercrs\Models\Cabins', # Другая модель с которой мы устанавливаем связь через сводную таблицу
            'table'    => 'mcmraak_rivercrs_decks_pivot', # Сводная таблица
            'key'      => 'deck_id', # Ключ в сводной таблице отображающий id этой модели
            'otherKey' => 'cabin_id' # Ключ в сводной таблице отображающий id  другой модели
        ]
    ];

    public $hasOne = [
        'parent' => [
            'Mcmraak\Rivercrs\Models\Decks',
            'key' => 'id',
            'otherKey' => 'parent_id',
        ],
    ];

    # Список родительских палуб
    function getParentIdOptions()
    {
        return [0 => ' -- '] + self::lists('name', 'id');
    }

    public function beforeUpdate()
    {
        if ($this->parent_id != $this->getOriginal('parent_id')) {
            if($this->getOriginal('parent_id') && $this->parent_id) {
                throw new ApplicationException("Нельзя поменять родителя {$this->getOriginal('parent_id')} > {$this->parent_id}");
            }
        }
    }

    public function afterUpdate()
    {
        $new_parent_id = $this->parent_id;
        $old_parent_id = $this->getOriginal('parent_id');
        if ($new_parent_id != $old_parent_id) {

            DB::table('mcmraak_rivercrs_decks_pivot')
                ->where('deck_id', $this->id)
                ->update([
                    'deck_id' => $new_parent_id
                ]);

            Flash::success("Ссылка установлена: {$this->id} > $new_parent_id");
        }
    }


}