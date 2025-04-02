<?php namespace Zen\Om\Components;

use Cms\Classes\ComponentBase;
use Zen\Om\Models\Category;
use Zen\Om\Models\Store;
use Zen\Om\Models\Item;

class OmRequest extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'zen.om::lang.components.om_request_name',
            'description' => 'zen.om::lang.components.om_request_desc'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
        $store_request = '';
        $store = null;

        $url = substr($_SERVER['REQUEST_URI'],1);

        $url = explode('?',$url);
        $url = $url[0];

        if(substr($url, -1) == '/') $url = substr($url, 0 , -1);
        if(substr($url, -5) == '.html') $url = substr($url, 0,-5);

        $stories = Store::get();
        foreach ($stories as $store)
        {
            if(strpos($url, $store->slug)===0){
                $store_request = substr(str_replace($store->slug,'', $url),1);
                break;
            }
        }

        /* if root category */
        if(!$store_request){
            $this->page['om_request'] = [

                'categories' => Category::where('store_id', $store->id)->where('parent_id',0)->get(),
                'breadcrumbs' => [[ 'name' => $store->name, 'slug' => $store->slug ]]
            ];
            return;
        }

        /* if category */
        $url_slugs = explode('/', $store_request);
        $last_slug = $url_slugs[count($url_slugs) - 1];
        $category = Category::where('slug', $last_slug)->first();
        if($category)
        {
            $this->page['om_request'] = [
                'category' => $category,
                'categories' => $category->categories,
                'breadcrumbs' => $category->breadcrumbs(),
            ];
            return;
        }

        /* if item */
        $item = Item::where('slug', $last_slug)->where('store_id', $store->id)->first();
        if($item)
        {
            $this->page['om_request'] = [
                'item' => $item,
                'breadcrumbs' => $item->category->breadcrumbs()
            ];
            return;
        }

        /* if not exist */
        return $this->controller->run('404');
    }
}
