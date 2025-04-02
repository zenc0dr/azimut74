<?php namespace Zen\Worker\Pools;


use Carbon\Carbon;
use View;
use Zen\Worker\Classes\Http;
use Exception;

class Gama extends RiverCrs
{
    function getGamaCruise($id, $cruises)
    {
        foreach ($cruises as $cruise) {

            if(!isset($cruise['ways']['way'])) {
                continue;
            }

            foreach ($cruise['ways']['way'] as $way) {

                $eds_id = $this->getGamaParam($way, 'iid');

                if ($eds_id == $id) {
                    return [
                        'gama_ship_id' => $this->getGamaParam($cruise, 'ship_iid'),
                        'gama_ship_name' => $this->getGamaParam($cruise, 'ship_name'),
                        'way' => $way,
                    ];
                };
            }
        }
    }

    function getGamaParam($arr, $param_name){
        if (isset($arr['@attributes'][$param_name])) {
            return trim($arr['@attributes'][$param_name]);
        }
        if (isset($arr[$param_name])) {
            return trim($arr[$param_name]);
        }
        return false;
    }

    function gamaRouteBolder($bold, $cruise)
    {
        $bold = explode(' - ', $bold);
        $way = $cruise['path']['point'];

        $waybill = [];

        $i = 0;
        $ii = count($way) - 1;

        foreach ($way as $point)
        {
            $town_name = $point['@attributes']['town_name'];
            $town_id = $this->getTownId($town_name, 'gama');
            $waybill[] = [
                'town' => $town_id,
                'excursion' => '',
                'bold' => (in_array($town_name, $bold)||!$i||$i==$ii)?1:0
            ];
            $i++;
        }

        return $waybill;
    }

    function gamaDesignSchedule($gama_cruise)
    {
        $gama_cruise_route = [];
        foreach ($gama_cruise['path']['point'] as $route) {
            $town_name = $route['@attributes']['town_name'];
            $start_time = $route['@attributes']['STS'];
            $start_time = date('d.m.Y H:i:s', strtotime($start_time));
            $end_time = $route['@attributes']['ETS'];
            $end_time = date('d.m.Y H:i:s', strtotime($end_time));

            $gama_cruise_route[] = [
                'town' => $town_name,
                'start' => $start_time,
                'end' => $end_time
            ];
        }

        $table_data = [];

        foreach ($gama_cruise_route as $item) {

            $town = $item['town']; # Нижний Новгород
            $full_date_1 = Carbon::parse($item['start']);
            $full_date_2 = Carbon::parse($item['end']);
            $diff_in_days = $full_date_2->diffInDays($full_date_1);
            $stay = $full_date_2->diffInSeconds($full_date_1);
            $stay = gmdate('H:i', $stay);

            if ($diff_in_days == 0) {
                $table_data[] = [
                    'date' => $full_date_1->format('d.m.Y'),
                    'town' => $town,
                    'arrival' => $full_date_1->format('H:i'),
                    'stay' => $stay,
                    'departure' => $full_date_2->format('H:i'),
                ];
            } else {
                $table_data[] = [
                    'date' => $full_date_1->format('d.m.Y'),
                    'town' => $town,
                    'arrival' => $full_date_1->format('H:i'),
                    'stay' => $stay,
                    'departure' => '',
                ];

                $table_data[] = [
                    'date' => $full_date_2->format('d.m.Y'),
                    'town' => $town,
                    'arrival' => '',
                    'stay' => '',
                    'departure' => $full_date_2->format('H:i'),
                ];
            }
        }
        return View::make('mcmraak.rivercrs::gama_schedule', ['table' => $table_data])->render();
    }

    function getGamaDeck($gama_deck_id)
    {
        $gama_deck = $this->stream->cache->get("GamaDecks.$gama_deck_id");

        if(!$gama_deck) {
            $http = new Http;
            $http_query = $http->setTimout($this->pool_state->timeout)
                ->query('https://api.gama-nn.ru/execute/view/deck/'.$gama_deck_id, 'xml');
            if($http_query->error) {
                return new Exception($http_query->error);
            }
            $gama_deck = $http_query->response;
            $this->stream->cache->put("GamaDecks.$gama_deck_id", $gama_deck);
        }

        $gama_deck = $gama_deck['deck'];
        $gama_deck_name = $this->getGamaParam($gama_deck, 'name');
        return $this->getDeck($gama_deck_name);
    }
}
