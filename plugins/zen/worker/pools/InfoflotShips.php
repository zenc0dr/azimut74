<?php namespace Zen\Worker\Pools;

use Mcmraak\Rivercrs\Classes\CacheSettings;
use Zen\Worker\Classes\ProcessLog;
use Exception;
use Carbon\Carbon;

class InfoflotShips extends Infoflot
{
    public function getCount()
    {
        $ships_page_1 = $this->riverQuery('https://restapi.infoflot.com/ships', 'json', [
            'key' => $this->stream->model->data['key'],
            'page'=> 1,
            'limit' => 100
        ], 'infoflot.100_ships.page_1');

        #if($ships_page_1 instanceof Exception) throw new Exception($ships_page_1);

        $pages_count = $ships_page_1['pagination']['pages']['total'];

        for ($i = 0; $i < $pages_count; $i++) {
            $this->stream->addJob('InfoflotShips@getPage', ['page' => $i + 1]);
        }
    }

    public function getPage($data)
    {
        $page = $data['page'];
        $response = $this->riverQuery('https://restapi.infoflot.com/ships', 'json', [
            'key' => $this->stream->model->data['key'],
            'page'=> $page,
            'limit' => 100
        ], 'infoflot.100_ships.page_'.$page);

        #if($response instanceof Exception) throw new Exception($response);

        $ships = $response['data'];

        $index = 0;
        foreach ($ships as $ship) {
            $this->stream->addJob('InfoflotShips@addShip', ['page' => $page, 'index' => $index]);
            $index++;
        }
    }

    public function addShip($data)
    {
        $page = $data['page'];
        $index = $data['index'];

        $ship = $this->stream->cache->get('infoflot.100_ships.page_'.$page)['data'][$index];

        if (CacheSettings::shipIsBad($ship['name'], 'infoflot')) {
            return;
        }

        $now = Carbon::now();

        $page = 1;
        while (true) {
            ProcessLog::add("Запрос набора круизов для теплохода: Страница $page");

            try {
                $ship_cruises = $this->riverQuery('https://restapi.infoflot.com/cruises', 'json', [
                    'key' => $this->stream->model->data['key'],
                    'ship' => $ship['id'],
                    'page' => $page,
                    'date' => date('Y-m-d'),
                    'limit' => 500
                ]);
            } catch (\Exception $ex) {
                // Not found
                if ($ex->getMessage() === 'Not found') {
                    break;
                }
            }

            $total = intval($ship_cruises['pagination']['pages']['total'] ?? null);

            #if($ship_cruises instanceof Exception) throw new Exception($ship_cruises);

            $cruises = @$ship_cruises['data'];
            if (!$cruises) {
                break;
            }

            foreach ($cruises as $cruise) {
                echo $cruise['id'] . '<br>';
                $cruise_date = Carbon::parse($cruise['dateStart']);

                if ($cruise_date < $now) {
                    continue;
                }
                $this->stream->addJob('InfoflotCruises@addCruise', ['data' => $cruise]);
            }

            if ($total >= $page) {
                break;
            }

            $page++;
        }
    }
}
