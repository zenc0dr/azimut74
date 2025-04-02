<?php namespace Zen\GroupTours\Api;

use Zen\GroupTours\Models\Tour;

class Debug extends Api
{
    # http://azimut.dc/zen/gt/api/debug:testApi
    public function testApi()
    {
        $order = master()->files()->arrayFromFile(storage_path('gt_order.json'));
        $response = $this->store('Order', $order);

        dd($response);

        //master()->files()->arrayToFile(['test' => 'ok'], storage_path('kriti_test.json'));
//        $input = 'Упячко!';
//        $data = $this->store('TestStore', ['input_data' => $input]);
//        dd($data);
    }

    # http://azimut.dc/zen/gt/api/debug:testTour?id=1
    public function testTour()
    {
        $tour_id = $this->input('id');
        $tour = Tour::find($tour_id);
        dd(
            collect($tour->waybill_objects)->get(1)->name,
            collect($tour->waybill_objects)->last()->name
        );
    }
}
