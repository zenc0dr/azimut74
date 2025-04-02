<?php namespace Srw\Catalog\Models;

use Model;
use Srw\Catalog\Models\Items;

/**
 * Model
 */
class Category extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\NestedTree; // Трейт для работы реордер-панели

    /*
     * Validation
     */
    public $rules = [
    ];

    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'srw_catalog_categorys';


    public $belongsTo = [
        'parent' => [
            'Srw\Catalog\Models\Category',
            'key' => 'parent_id'
        ]
    ];

    // Get items for category
    public $hasMany = [
        'goods' => [
            'Srw\Catalog\Models\Items',
            'key' => 'category_id'
        ]
    ];

    // Change parent in items
    public function afterDelete ()
    {
        $category_id = $this->id;
        Items::where('category_id', $category_id)->update(['category_id' => null]);
    }

    // Get size of category
    function getItemsCountAttribute(){
        return $this->goods()->count();
    }
    /*
     * @return array ['key' => 'value']
     * Формирование dropdown-списка для формы создания/изменения категории, поле "Родительская категория"
     */
    public function getParentIdOptions() {
        if(Category::count()){
            return [0 => 'Нет родителя'] + Category::getRootList('name', 'id', '- ');
        }
        return [0 => 'Нет родителя'];
    }

    /* Мутатор для поля parent_id преобразующий 0 в NULL */
    public function setParentIdAttribute($value)
    {
        if($value == 0) {
            $this->attributes['parent_id'] = NULL;
        } else {
            $this->attributes['parent_id'] = $value;
        }
    }

}
