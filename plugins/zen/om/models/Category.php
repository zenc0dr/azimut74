<?php namespace Zen\Om\Models;

use Model;
use Illuminate\Support\Collection;
use Log;

/**
 * Model
 */
class Category extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\NestedTree;
    
    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;
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
    public $table = 'zen_om_categories';

    public $hasMany = [
        'items' => [
            'Zen\Om\Models\Item',
            'key' => 'category_id'
        ],
        'categories' => [
            'Zen\Om\Models\Category',
            'key' => 'parent_id'
        ],
    ];

    public $belongsTo = [
        'parent' => [
            'Zen\Om\Models\Category',
            'key' => 'parent_id'
        ],
        'store' => [
            'Zen\Om\Models\Store',
            'key' => 'store_id'
        ],
    ];

    public $attachMany = [
        'images' => ['System\Models\File', 'order' => 'sort_order', 'delete' => true],
    ];

    /* Store filter */
    public function scopeFilterStores($query, $id)
    {
        return $query->whereHas('store', function($q) use ($id) {
            $q->whereIn('id', $id);
        });
    }

	public function sortedCategories()
	{
		return $this->categories()->orderBy('sort_order')->get();
	}

    public function storeItems($params=null)
    {
        $defaults = [
            'store_id' => null,
            'paginate' => null,
            'page' => intval((isset($_REQUEST['page'])) ? $_REQUEST['page'] : 1)
        ];
        if($params) {
            $params = array_replace($defaults, $params);
        } else {
            $params = $defaults;
        }

        if($params['paginate'])
        return $this->items()
            ->where(function($query) use ($params) {
                if($params['store_id']){
                    $query->where('store_id', $params['store_id']);
                }
            })->paginate($params['paginate'], $params['page']);
        return $this->items()
            ->where(function($query) use ($params) {
                if($params['store_id']){
                    $query->where('store_id', $params['store_id']);
                }
            })->get();
    }

    /* Dropdown stores */
    public function getStoreIdOptions() {
        return Store::lists('name', 'id');
    }

    public function getParentIdOptions() {
        if(Category::count()){
            $listTree = Category::getRootList('name', 'id', '- ');
            unset($listTree[$this->id]);
            return [0 => '<span class="noparent">Не выбрано</span>'] + $listTree;
        }
        return [0 => 'Нет родителя'];
    }

    public function setParentIdAttribute($value) {
        
        # Create root
        # Save root
        if(!isset($this->parent_id) && $this->parent_id == 0 && $value == 0){
            $this->attributes['parent_id'] = 0;
        }

        # Move node to root
        if($this->parent_id > 0 && $value == 0){
            $this->makeRoot();
        }

        # Move root to node
        # Move node to node
        if($this->parent_id >= 0 && $value > 0){
            $this->attributes['parent_id'] = $value;
        }
    }

    public function createUrlCache(){
        \DB::table('zen_om_categories')
            ->where('id', $this->id)
            ->update([
                'url_cache' => $this->url()
            ]);
    }

    public function fastRecache($old,$new){
        \DB::unprepared("UPDATE zen_om_categories SET url_cache = replace(url_cache, '/$old', '/$new') WHERE url_cache REGEXP '/$old$';");
        \DB::unprepared("UPDATE zen_om_categories SET url_cache = replace(url_cache, '/$old/', '/$new/');");
        \DB::unprepared("UPDATE zen_om_items SET url_cache = replace(url_cache, '/$old', '/$new') WHERE url_cache REGEXP '/$old$';");
        \DB::unprepared("UPDATE zen_om_items SET url_cache = replace(url_cache, '/$old/', '/$new/');");
    }

    /* Events */
    public $original_slug, $changed_slug;
    public function afterFetch()
    {
        $this->original_slug = $this->attributes['slug'];
    }
    public function beforeSave()
    {
        $this->changed_slug = $this->attributes['slug'];
    }
    public function afterSave()
    {
        $this->createUrlCache();
    }
    public function afterUpdate()
    {
        $this->fastRecache($this->original_slug,$this->changed_slug);
    }
    public function afterDelete()
    {
        Item::where('category_id', $this->id)->update(['category_id' => 0]);
        $this->fastRecache($this->original_slug,'');
    }

    /* Data methods */
    public
        $url_chain = [],
        $chain = [],
        $store_slug,
        $store_name;

    public function url()
    {
        $chain = $this->buildChain();
        $url_chain = '';
        foreach ($chain as $item)
        {
            $url_chain .= '/'.$item['slug'];
        }

        if(Setting::isCategoryHtmlfix()) $url_chain .= '.html';

        //return '/' . $this->store_slug . $url_chain;
		return $url_chain;
    }

    public function breadcrumbs()
    {
        $chain = $this->buildChain();

        $breadcrumbs = [];
        $url_chain = $this->store_slug;

        $breadcrumbs[] = [
            'name' => $this->store_name,
            'slug' => '/' . $this->store_slug
        ];

        foreach ($chain as $item)
        {
            $url_chain .= '/'.$item['slug'];
            $breadcrumbs[] = [
                'name' => $item['name'],
                'slug' => '/' . $url_chain
            ];
        }

        return $breadcrumbs;
    }

    public function buildChain()
    {
        $this->parentCategory($this);
        $this->store_slug = $this->store->slug;
        $this->store_name = $this->store->name;
        return array_reverse($this->chain);
    }

    private function parentCategory($category)
    {
        if(!$category->parent){
            $this->chain[] = [
                'slug' => $category->slug,
                'name' => $category->name,
            ];
            return;
        } else {
            $this->chain[] = [
                'slug' => $category->slug,
                'name' => $category->name,
            ];
            $this->parentCategory($category->parent);
        }
    }


    /* All items in this category and subcategories */
    public function hasItems($params=null)
    {
        $defaults = [
            'store_id' => null,
            'paginate' => null,
            'page' => intval((isset($_REQUEST['page'])) ? $_REQUEST['page'] : 1)
        ];
        if($params) {
            $params = array_replace($defaults, $params);
        } else {
            $params = $defaults;
        }

        $childrens_id = $this->allChildren()->get()->pluck('id')->toArray();
        $childrens_id[] = $this->id; // Добавляю и её саму
        asort($childrens_id);
        $childrens_id = array_values($childrens_id);

        if($params['paginate'])
        {
            return Item::where(function ($query) use ($params, $childrens_id) {
                if ($params['store_id']) {
                    $query->where('store_id', $params['store_id']);
                }
                $query->whereIn('category_id', $childrens_id);
            })->paginate($params['paginate'], $params['page']);
        }
        else
        {
            return Item::where(function ($query) use ($params, $childrens_id) {
                if ($params['store_id']) {
                    $query->where('store_id', $params['store_id']);
                }
                $query->whereIn('category_id', $childrens_id);
            })->get();
        }
    }

	protected $jsonable = [
        'cat_links',
    ];

}