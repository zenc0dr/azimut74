<?php namespace Zen\Om\Models;

use Model;

/**
 * Model
 */
class Item extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    /*
     * Validation
     */
    public $rules = [
        'slug' => 'required|unique:zen_om_items|'.
                  'required|unique:zen_om_categories',
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'zen_om_items';

    public $fieldsDump;

    public $belongsTo = [
        'category' => [
            'Zen\Om\Models\Category',
            'key' => 'category_id'
        ],
        'storage' => [
            'Zen\Om\Models\Storage',
            'key' => 'storage_id'
        ],
        'store' => [
            'Zen\Om\Models\Store',
            'key' => 'store_id'
        ],
        'brand' => [
            'Zen\Om\Models\Brand',
            'key' => 'brand_id'
        ],
    ];

    public $attachMany = [
        'images' => ['System\Models\File', 'order' => 'sort_order', 'delete' => true],
    ];

    public $belongsToMany = [
        'customs' => [
            'Zen\Om\Models\Field', # Другая модель с которой мы устанавливаем связь через сводную таблицу
            'table'    => 'zen_om_fields_pivot', # Сводная таблица
            'key'      => 'item_id', # Ключ в сводной таблице отображающий id этой модели
            'otherKey' => 'field_id', # Ключ в сводной таблице отображающий id  другой модели
        ]
    ];


    /* Accessories */
    public function getAccessoryOptions()
    {
        $items = self::get();
        $returnArr = [0 => '--'];
        foreach ($items as $item)
        {
            $returnArr[$item->id] = [
               "id:{$item->id} " .$item->name
            ];
        }
        return $returnArr;
    }

    public function getAccessoriesAttribute($value)
    {
        $ids = explode(',', $value);
        $items = [];
        foreach ($ids as $id)
        {
            $items[] = [
                'accessory' => $id
            ];
        }
        return $items;
    }
    public function setAccessoriesAttribute($value)
    {
        $ids = [];

        foreach ($value as $item)
        {
            if($item['accessory']) $ids[] = $item['accessory'];
        }
        $this->attributes['accessories'] = join(',', $ids);
    }

    /* Category filter */
    public function scopeFilterCategories($query, $id)
    {
        return $query->whereHas('category', function($q) use ($id) {
            $q->whereIn('id', $id);
        });
    }

    /* Store filter */
    public function scopeFilterStores($query, $id)
    {
        return $query->whereHas('store', function($q) use ($id) {
            $q->whereIn('id', $id);
        });
    }

    /* Storage filter */
    public function scopeFilterStorages($query, $id)
    {
        return $query->whereHas('storage', function($q) use ($id) {
            $q->whereIn('id', $id);
        });
    }

    /* Dropgown category */
    public function getCategoryIdOptions() {
        $categorys = new Category;
        return $categorys->getParentIdOptions();
    }

    /* Dropdown stores */
    public function getStoreIdOptions() {
        return Store::lists('name', 'id');
    }

    /* Dropdown storages */
    public function getStorageIdOptions() {
        return [0 => '<span class="noparent"> -- </span>'] + Storage::lists('name', 'id');
    }

    /* Dropdown brands */
    public function getBrandIdOptions() {
        return [0 => '<span class="noparent"> -- </span>'] + Brand::lists('name', 'id');
    }

    public function setPriceAttribute($value)
    {
        if($value == "") {
            $this->attributes['price'] = 0;
        } else {
            $this->attributes['price'] = $value;
        }
    }

    public function setQuantityAttribute($value)
    {
        if($value == "") {
            $this->attributes['quantity'] = 0;
        } else {
            $this->attributes['quantity'] = $value;
        }
    }

    public function setHitsAttribute($value)
    {
        if($value == "") {
            $this->attributes['hits'] = 0;
        } else {
            $this->attributes['hits'] = $value;
        }
    }

    public function getFieldIdOptions()
    {
        return Field::lists('name', 'id');
    }

    public function getCustomFieldsAttribute()
    {
        $records = FieldPivot::where('item_id', $this->id)->orderBy('field_id')->get();

        if(!$records->count()) return;

        $repeater = [];
        foreach ($records as $value) {
            $repeater[] = [
                'field_id' => $value->field_id,
                'int_val' => $value->int_val,
                'str_val' => $value->str_val,
                'html_val' => $value->html_val,
            ];
        }
        return $repeater;
    }

    public function setCustomFieldsAttribute($value)
    {
        $this->fieldsDump = $value;
    }

    public function saveFields()
    {
        if(!$this->fieldsDump) return;
        FieldPivot::where('item_id', $this->id)->delete();
        $inserts = [];
        foreach ($this->fieldsDump as $value)
        {
            $inserts[] = [
                'item_id' => $this->id,
                'field_id' => $value['field_id'],
                'int_val' => ($value['int_val']) ? $value['int_val'] : 0,
                'str_val' => $value['str_val'],
                'html_val' => $value['html_val']
            ];
        }

        FieldPivot::insert($inserts);

    }

    public function createUrlCache()
    {
        \DB::table('zen_om_items')
            ->where('id', $this->id)
            ->update([
                'url_cache' => $this->url()
            ]);
    }

    public static function fillEmptyUrlCache()
    {
        $items = self::where('url_cache', '')->get();
        foreach($items as $item)
        {
            $item->createUrlCache();
        }
    }

    public function afterSave()
    {
        $this->saveFields();
        $this->createUrlCache();
    }

    public function afterDelete()
    {
        FieldPivot::where('item_id', $this->id)->delete();
    }

    /* Data methods */
    public function url()
    {
        $category = $this->category;

        if($category){
            $category_url = $this->category->url();
            if(substr($category_url, -5) == '.html') $category_url = substr($category_url, 0,-5);
            $return_url = $category_url . '/' . $this->slug;
        } else {
            $return_url = '/' . $this->store->slug . '/' . $this->slug;
        }

        if(Setting::isItemHtmlfix()) $return_url .= '.html';
        return $return_url;
    }

    public function custom($params = null)
    {
        if(!$params) return;

        if($params > 0){
            $fieldType = Field::find($params);
        } else {
            $fieldType = Field::where('code', $params)->first();
        }

        if(!$fieldType) return;

        return [
            'name' => $fieldType->name,
            'values' => FieldPivot::where('item_id',$this->id)->where('field_id', $fieldType->id)->get()
        ];

    }

    public function getAccessories()
    {
        $items_ids = $this->getOriginal('accessories');
        if($items_ids) {
            $items_ids = explode(',', $items_ids);
            $ids_ordered = join(',', $items_ids);
            return Item::whereIn('id', $items_ids)
                ->orderByRaw(\DB::raw("FIELD(id, $ids_ordered)"))
                ->get();
        }
    }

}