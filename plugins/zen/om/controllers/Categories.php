<?php namespace Zen\Om\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Zen\Om\Models\Category;
use Zen\Om\Models\Item;
use Cache;
use Illuminate\Support\Facades\Log;

class Categories extends Controller
{
    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController','Backend\Behaviors\ReorderController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public function __construct()
    {
        $this->addCSS('/plugins/zen/om/assets/css/backend.css');
        parent::__construct();
        BackendMenu::setContext('Zen.Om', 'om-main', 'om-items');
    }

    public static function updateUrlCache($page, $steep)
    {
        $records = \Zen\Om\Models\Category::paginate($steep, $page);
        foreach($records as $record)
        {
            $record->createUrlCache();
        }
        return $records->count();
    }

    public static function getLinks()
    {


        return Cache::get('octomarket.links', function() {
            $links = [];

            $categories = Category::get();
            if($categories->count()){
                foreach ($categories as $category)
                {

                    $links[] = [
                        'url' => $category->url_cache,
                        'title' => $category->name,
                    ];
                }
            }

            $items = Item::get();
            if($items->count()) {
                foreach ($items as $item)
                {

                    $links[] = [
                        'url' => $item->url_cache,
                        'title' => $item->name,
                    ];
                }
            }
            return ['items' => $links];
        });
    }

}