<?php namespace Zen\Om\Controllers;

use Backend\Classes\Controller;
use BackendMenu;

use RainLab\User\Models\User;
use Zen\Om\Models\Item;
use Zen\Om\Models\Order;
use Zen\Om\Models\OrderItem;
use Zen\Om\Models\Setting;
class Orders extends Controller
{
    public $implement = ['Backend\Behaviors\ListController','Backend\Behaviors\FormController','Backend\Behaviors\ReorderController'];
    
    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public function __construct()
    {
        $this->addCSS('/plugins/zen/om/assets/css/backend.css');
        parent::__construct();
        BackendMenu::setContext('Zen.Om', 'om-main', 'om-orders');
    }

    /**
     * @var int $user_id User id
     * @var array $items_ids + quantity!!! Items in order
     * @var array $bag Order options (payment, delivery, comment etc.)
     * @return bool
     */
    public static function addOrder($user_id, $order_items, $options)
    {
        $bag = null;
        if(isset($options['bag'])) $bag = $options['bag'];

        $comments = null;
        if(isset($options['comments'])) $comments = $options['comments'];

        $payment_id = null;
        if(isset($options['payment_id'])) $payment_id = $options['payment_id'];

        $delivery_id = null;
        if(isset($options['delivery_id'])) $delivery_id = $options['delivery_id'];


        $item_id = Setting::itemId();
        if(!count($order_items)) return false;

        $items_ids = array_keys($order_items);


        $user = User::find($user_id);
        if(!$user) return false;

        $items = Item::whereIn($item_id, $items_ids)->get();

        $insert_items = [];
        foreach ($items as $item)
        {
            $qty = $order_items[$item->{$item_id}];
            $insert_items[] = [
                'item_id' => $item->{$item_id},
                'item_qty' => $qty,
                'item_price' => $item->price
            ];
        }
        $order = new Order;
        $order->user_id = $user->id;
        $order->status_id = 1;
        if($bag)
            $order->bag = $bag;
        if($comments)
            $order->comments = $comments;
        if($payment_id)
            $order->payment_id = $payment_id;
        if($delivery_id)
            $order->delivery_id = $delivery_id;
        $order->items = $insert_items;
        $order->save();
        return [
            'order_id' => $order->id,
            'summ' => $order->summ,
        ];
    }

}