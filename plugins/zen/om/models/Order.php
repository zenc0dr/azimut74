<?php namespace Zen\Om\Models;

use Model;

/**
 * Model
 */
class Order extends Model
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
    public $table = 'zen_om_orders';

    /* Relations */
    public $belongsTo = [
        'user' => [
            'RainLab\User\Models\User'
        ],
        'status' => [
            'Zen\Om\Models\Status'
        ],
        'payment' => [
            'Zen\Om\Models\Payment'
        ],
        'delivery' => [
            'Zen\Om\Models\Delivery'
        ],
    ];

    /* Status filter */
    public function scopeFilterStatuses($query, $id)
    {
        return $query->whereHas('status', function($q) use ($id) {
            $q->whereIn('id', $id);
        });
    }
    /* User filter */
    public function scopeFilterUsers($query, $id)
    {
        return $query->whereHas('user', function($q) use ($id) {
            $q->whereIn('id', $id);
        });
    }
    /* Payment filter */
    public function scopeFilterPayments($query, $id)
    {
        return $query->whereHas('payment', function($q) use ($id) {
            $q->whereIn('id', $id);
        });
    }
    /* Delivery delivery */
    public function scopeFilterDeliveries($query, $id)
    {
        return $query->whereHas('delivery', function($q) use ($id) {
            $q->whereIn('id', $id);
        });
    }

    public function getItems(){
        $item_id = Setting::itemId();
        $items_arr = $this->items;
        $items_ids = [];
        $h = [];
        foreach ($items_arr as $i)
        {
            $items_ids[] = $i['item_id'];
            $h[$i['item_id']] = [
                'qty' => $i['item_qty'],
                'price' => $i['item_price']
            ];
        }
        $items = Item::whereIn($item_id, $items_ids)->get();
        $return = [];
        foreach($items as $item){
            $return[] = [
                'id' => $item->id,
                'vendor_code' => $item->vendor_code,
                'name' => $item->name,
                'price' => $h[$item->{$item_id}]['price'],
                'quantity' => $h[$item->{$item_id}]['qty'],
            ];
        }
        return $return;
    }

    public function getItemsAttribute($json)
    {
        return json_decode($json, true);
    }

    public function setItemsAttribute($value)
    {
        $this->attributes['items'] = json_encode ($value, JSON_UNESCAPED_UNICODE);
    }

    public function getCommentsAttribute($json)
    {
        return json_decode($json, true);
    }

    public function setCommentsAttribute($value)
    {
        $this->attributes['comments'] = json_encode ($value, JSON_UNESCAPED_UNICODE);
    }

    public function beforeSave(){
        $order_summ = 0;
        foreach ($this->items as $item)
        {
            $order_summ += $item['item_price'] * $item['item_qty'];
        }
        $this->attributes['summ'] = $order_summ;
    }

    /* Data methods */

    public function addComment($name, $message)
    {
        $comments = $this->comments;
        $comments[] = [
            'com_user' => $name,
            'com_message' => $message
        ];
        $this->attributes['comments'] = $comments;
    }


}