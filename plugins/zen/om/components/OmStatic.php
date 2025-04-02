<?php namespace Zen\Om\Components;

use Cms\Classes\ComponentBase;
use Zen\Om\Models\Category;
use Zen\Om\Models\Item;
use Zen\Om\Models\Field;

class OmStatic extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'zen.om::lang.components.om_static_name',
            'description' => 'zen.om::lang.components.om_static_desc'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRun(){
        $this->page['om_static'] = $this;
    }

    public function get($params = null){
        $store_id = null;
        $category_id = 0;
        if($params)
        {
            $option = key($params);

            if($option == 'shop_id'){
                $store_id = $params[$option];
            }
            if($option == 'category_id'){
                $category_id = $params[$option];
            }
        }
        if($store_id){
            return Category::where('store_id', $store_id)->where('parent_id', 0)->get();
        }
        if($category_id) return Category::find($category_id)->categories;
        return Category::where('parent_id', 0)->get();
    }

    public function field_items($params)
    {
        $defaults = [
            'code' => null,
            'order' => null,
            'limit' => null
        ];

        $order_type = null;


        $params = array_replace($defaults, $params);

        if($params['order']){
            $order_arr = explode(':',$params['order']);
            if(count($order_arr)==2){
                $params['order'] = $order_arr[0];
                $order_type = $order_arr[1];
            }
        }

        if(!$params['code']) return;
        if($params['code'] > 0){
            $fieldType = Field::find($params['code']);
        } else {
            $fieldType = Field::where('code', $params['code'])->first();
        }
        if(!$fieldType) return;

        if($params['order']) {
                $items = \DB::
                table('zen_om_fields as fields')->
                    where('code', $params['code'])->
                    join(
                        'zen_om_fields_pivot as pivot',
                        'pivot.field_id',
                        '=',
                        'fields.id'
                        )->
                    join(
                        'zen_om_items as items',
                        'items.id',
                        '=',
                        'pivot.item_id'
                        )->
                    select('items.id')->
                    orderBy(
                    'pivot.'.$params['order'],
                             $order_type
                        )->
                    limit($params['limit'])->
                    get();
                if(!$items->count()) return;
                $items_ids = [];
                foreach($items as $item)
                {
                    $items_ids[] = $item->id;
                }
                $ids_ordered = join(',', $items_ids);
                return Item::whereIn('id', $items_ids)
                    ->orderByRaw(\DB::raw("FIELD(id, $ids_ordered)"))
                    ->get();
        }
        return $fieldType->items->take(2);
    }
}
