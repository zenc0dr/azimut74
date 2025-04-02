<?php namespace Srw\Catalog\Components;

use Cache;
use Request;
use Cms\Classes\ComponentBase;
use System\Classes\ApplicationException;
use \Srw\Core\Controllers\Core;
use Srw\Catalog\Models\Category;
use Srw\Catalog\Models\Items;

/* ## Template twig vars ##

   getCategoryItems    - Categories list
   catalogitems        - Items in select category
   catalog_card        - One catalog card
   catalogBreadcrumbs  - Bread crumbs

*/

class Catalog extends ComponentBase
{
    public $catalogBreadcrumbs = [];
    public $urlChain = '/catalog';

    public function componentDetails()
    {
        return [
            'name'        => 'MCM Catalog',
            'description' => 'Каталог товара'
        ];
    }

    public function onRun()
    {
        $slug = Core::slug($this);

        if(!$slug) {
            $slug = null;
            $this->page['categoryitems'] = Category::whereNull('parent_id')->get();
            $this->getCategoryItems(null);
        } else {
            $this->page['slug'] = '/'.$slug;
            $urlArr = explode('/', $slug);
            $urlCount = count($urlArr);
            $parent_cat_id = null;

            for($i=0;$i<$urlCount;$i++){

                if(!$parent_cat_id){
                    $catId = Category::where('slug','/'.$urlArr[$i])->first();
                    if(!$catId) return $this->getCard($urlArr[$i]);

                    $parent_cat_id = $catId->id;
                } else {
                    $catId = Category::where('parent_id',$parent_cat_id)
                                     ->where('slug','/'.$urlArr[$i])
                                     ->first();
                    if(!$catId) return $this->getCard($urlArr[$i]);
                    $parent_cat_id = $catId->id;
                }

                # Last slug
                if($i==$urlCount-1){
                    $this->categoryList($parent_cat_id);
                }

                $this->urlChain .= $catId->slug;
                $this->catalogBreadcrumbs[] = [
                         'slug' => $this->urlChain,
                         'name' => $catId->name,
                     ];
                }

                $this->page['catalogBreadcrumbs'] = $this->catalogBreadcrumbs;
        }
    }

    public function categoryList($parent_cat_id)
    {
        $this->page['categoryitems'] = Category::where('parent_id',$parent_cat_id)->get();
        #if(!count($this->page['categoryitems'])) $this->getCategoryItems($parent_cat_id);
        $this->getCategoryItems($parent_cat_id);
    }

    public function getCategoryItems($category_id)
    {
        #@TODO: Send paginate size to settings
        $this->page['catalogitems'] = Items::where('active',1)->where('category_id',$category_id)->paginate(15);

        $categoryInfo = Category::find($category_id);

        # Meta tags for category
        if($categoryInfo) {
            $this->page['octositeTitle'] = $categoryInfo->seo_title;
            $this->page['octositeDescription'] = $categoryInfo->seo_description;
            $this->page['octositeKeywords'] = $categoryInfo->seo_keywords;
        }
    }

    public function getCard($slug)
    {
        $card = Items::where('active',1)->where('slug','/'.$slug)->first();
        if(!$card) return $this->controller->run('404');

        $this->urlChain .= $card->slug;
        $this->catalogBreadcrumbs[] = [
              'slug' => $this->urlChain,
              'name' => $card->name
        ];

        $this->page['catalogBreadcrumbs'] = $this->catalogBreadcrumbs;
        $this->page['catalog_card'] = $card;

        # Seo tags

        $this->page['octositeTitle'] = $card->seo_title ?: $card->name;
        $this->page['octositeDescription'] = $card->seo_description;
        $this->page['octositeKeywords'] = $card->seo_keywords;
    }

}
