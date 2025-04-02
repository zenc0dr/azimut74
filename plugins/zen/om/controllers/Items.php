<?php namespace Zen\Om\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Input;
use Illuminate\Support\Facades\Log;
use Twig;
use Zen\Om\Models\Item;

class Items extends Controller
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
        $records = \Zen\Om\Models\Item::paginate($steep, $page);
        foreach($records as $record)
        {
            $record->createUrlCache();
        }
        return $records->count();
    }

    public  function onNewcategory()
    {
        $categories = new \Zen\Om\Models\Category;
        return Twig::parse(
            file_get_contents(__DIR__.'/../views/movetocategory.htm'),
            [
                'items_ids'=>join(',',Input::get('checked')),
                'categories'=> $categories->getParentIdOptions()
            ]
        );
    }

    public function onMoveitems()
    {
        $category_id = Input::get('category_id');
        $items_ids = explode(',', Input::get('items_ids'));
        Item::WhereIn('id', $items_ids)
            ->update([
                    'category_id'=>$category_id,
                    'url_cache'=>''
            ]);
        Item::fillEmptyUrlCache();
    }

}