<?php

namespace Zen\Dolphin\Api;
use Zen\Dolphin\Classes\Core;
use Cache;

class Ext extends Core
{
    # ПОисковый запрос с посадочной страницы вида
    # http://azimut.dc/zen/dolphin/api/ext:search
    public function search()
    {
        $search_query = [
            'geo_objects' => $this->input('geo_objects'),
            'date_of' => $this->input('date_of'),
            'date_to' => $this->input('date_to'),
            'date' => $this->input('date'),
            'tour_id' => $this->input('tour_id'),
            'days' => $this->input('days'),
            'adults' => $this->input('adults'),
            'childrens' => $this->input('childrens'),
            'list_type' => $this->input('list_type'),
        ];

        $cache_key = md5($this->json($search_query, true));

        if (Cache::has($cache_key)) {
            echo Cache::get($cache_key);
            return;
        }

        $results = $this->store('ExtSearch')->query($search_query);
        $output = $this->json($results, true);
        Cache::add($cache_key, $output, 180);
        echo $output;
    }

    # http://azimut.dc/zen/dolphin/api/ext:schedule?limit=100
    public function schedule()
    {
        $limit = $this->input('limit');
        $cache_key = 'dolphin.schedule:' . $limit;


        if (Cache::has($cache_key)) {
            $table_data = Cache::get($cache_key);
        } else {
            $table_data = $this->store('ExtSchedule')->get($limit);
            Cache::add($cache_key, $table_data, 1440);
        }

        $this->json($table_data);
    }
}
