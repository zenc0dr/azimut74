<?php namespace Srw\Catalog\Models;

use Model;
#use Srw\Catalog\Models\Category;
/**
 * Model
 */
class Items extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /*
     * Validation
     */
    public $rules = [
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'srw_catalog_items';


    /* Relationships */
    public $belongsTo = [
        'category' => [
            'Srw\Catalog\Models\Category'
        ],
        'brand' => [
            'Srw\Catalog\Models\Brands'
        ]
    ];
    public $attachMany = [
        'images' => ['System\Models\File', 'order' => 'sort_order', 'delete' => true],
    ];

    public $urlPath;

    public $runCount;

    /* for filter in adminpan for category */
    public function scopeFilterCategories($query, $category)
    {
        return $query->whereHas('category', function($q) use ($category) {
            $q->whereIn('id', $category);
        });
    }

    /* for filter in adminpan for brands */
    // public function scopeFilterBrands($query, $brands)
    // {
    //     return $query->whereHas('brands', function($q) use ($brands) {
    //         $q->whereIn('id', $brands);
    //     });
    // }
    
    /* Dropgown category */
    public function getCategoryIdOptions() {
        $categorys = new \Srw\Catalog\Models\Category;
        return $categorys->getParentIdOptions();
    }

    /* Brand Options */
    public function getBrandIdOptions() {
        return [0 => 'No brand'] + Brands::lists('name', 'id');
        //return [0 => 'No brand'];
    }

    /* Мутатор для поля brand_id преобразующий 0 в NULL */
    public function setParentIdAttribute($value)
    {
        if($value == 0) {
            $this->attributes['brand_id'] = NULL;
        } else {
            $this->attributes['brand_id'] = $value;
        }
    }

    /* Images count */
    public function getFillImagesAttribute()
    {
        return $this->images()->count();
    }
    /* sDesc trigger */
    public function getFillSdescAttribute()
    {
        return mb_strlen($this->sdesc);
    }
    public function getFillFdescAttribute()
    {
        return mb_strlen($this->fdesc);
    }

    /* Save category */
    public function setCategoryIdAttribute($value)
    {
        if($value == 0) {
            $this->attributes['category_id'] = NULL;
        } else {
            $this->attributes['category_id'] = $value;
        }
    }

    # Get full url address for catalog card
    public function url()
    {

        if(!$this->category_id){
            return $this->slug;
        } else {
            $this->getParentSlug($this->category_id);
            return $this->urlPath . $this->slug;
        }
    }

    public function getParentSlug($category_id)
    {
        $this->runCount ++;

        // if($this->runCount == 1){dd('$category_id='.$category_id);}

        # Get exemplar of model
        $category_item = Category::where('id', $category_id)->first();

        # If no item
        if(!$category_item) return;

        # If parent_id = NULL
        if($category_item->parent_id === null)
        {
            $this->urlPath = $category_item->slug . $this->urlPath;
            return;
        }

        # Concatenation element to url path for exemplar of model
        $this->urlPath = $category_item->slug . $this->urlPath;

        # Recursion
        $this->getParentSlug($category_item->parent_id);

    }


}
