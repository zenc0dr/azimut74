<?php namespace Zen\Dolphin\Store;

use Zen\Dolphin\Classes\Core;


class ExtSchedule extends Core
{
    public function get($limit = null, $inside_query = null)
    {
        $query = $inside_query ?: [
            'geo_objects' => [],
            'date_of' => now()->format('d.m.Y'),
            'date_to' => null,
            'days' => [],
            'adults' => 1,
            'childrens' => [],
            'list_type' => 'schedule'
        ];

        # Жёсткое определение атрибута
        $query['list_type'] = 'schedule';

        $results = $this->store('ExtSearch')->query($query);

        $result_items = $results['result_items'];

        if ($limit) {
            $result_items = collect($result_items)->take($limit)->toArray();
        }

        $schedule_table = [];
        foreach ($result_items as $item) {
            $dates = explode(' - ', $item['date_desc']);
            $schedule_table[] = [
                'departure' => $dates[0],
                'arrival' => $dates[1],
                'days' => $item['days'],
                'tour_name' => $item['tour_name'],
                'url' => $this->buildUrl($item, $query),
                'waybill' => $item['waybill'],
                'price' => $item['price']
            ];
        }

        return $schedule_table;
    }

    public function buildUrl($item, $query)
    {
        $query_data = [
            'ad' => $query['adults'],
            'ch' => $query['childrens'],
            'ds' => $query['days'] ?: 'all',
            'go' => $query['geo_objects'],
            'id' => $item['id'],
            'dt' => $item['date']
        ];

        return '/ex-tours/ext/booking?d=' . $this->json($query_data, true);
    }
}
