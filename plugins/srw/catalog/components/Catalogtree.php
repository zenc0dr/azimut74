<?php namespace Srw\Catalog\Components;

use Cache;
use Request;
use Cms\Classes\ComponentBase;
use System\Classes\ApplicationException;
use \Srw\Core\Controllers\Core;
use Srw\Catalog\Models\Category;

class Catalogtree extends ComponentBase
{
    public $items_arr = [];

    public function componentDetails()
    {
        return [
            'name'        => 'MCM CatalogTree',
            'description' => 'Структура категорий'
        ];
    }


    public function defineProperties()
    {
        //$categorys = new \Srw\Catalog\Models\Category;

        return [
            'catalogUrl' => [
                'title'             => 'URL Катаклога',
                'type'              => 'string',
                'default'           => '/catalog/',
            ],
        ];
    }

    public function onRun()
    {
        #$rootnode_id = $this->property('rootnode');
        #if(!$rootnode_id) $rootnode_id = NULL;

        $this->getCatalog();
    }

    /* Get array with catalog structure */
    public function getCatalog()
    {
        $categories = Category::get();
        $categoriesArr = $this->getArr($categories);
        $outArr = $this->jsonFitter($categoriesArr);
        $this->page['catalogtree'] = $outArr;
        $this->page['catalogUrl'] = $this->property('catalogUrl');
        return $outArr;
    }

    public function getArr($resultQuery)
    {
        $outArr = [];
        foreach ($resultQuery as $v) {
            $outArr[] = [
                'id' => $v->id,
                'name' => $v->name,
                'slug' => $v->slug,
                'image' => $v->image,
                'count' => $v->goods->count(),
                'parent_id' => $v->parent_id,
            ];
        }
        return $outArr;
    }

    public function jsonFitter($arr)
    {
        $jsonString = '';

        foreach ($arr as $v) {

            $parent_id = $v['parent_id'];

            $jnode = '{"id":"'.$v['id']
                    .'", "name":"'.$v['name']
                    .'", "slug":"'.$v['slug']
                    .'", "image":"'.$v['image']
                    .'", "count":"'.$v['count']
                    .'", "items":[##'.$v['id'].'##]}';


            if($parent_id) {
                $jsonString = str_replace('##'.$parent_id.'##', $jnode.'##'.$parent_id.'##', $jsonString);
            } else {
                $jsonString .= $jnode;
            }
        }
        return Core::jsonToggle($jsonString);
    }

}
