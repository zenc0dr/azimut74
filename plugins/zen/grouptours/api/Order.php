<?php namespace Zen\GroupTours\Api;

class Order extends Api
{
    # http://azimut74/zen/gt/api/order:send
    public function send()
    {
        $order = $this->input('order');

        #master()->files()->arrayToFile($order, storage_path('gt_order.json'));

        $response = $this->store('Order', $order);
        $this->json($response);
    }
}
