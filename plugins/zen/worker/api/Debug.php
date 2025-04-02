<?php namespace Zen\Worker\Api;

use Carbon\Carbon;
use Zen\Cabox\Classes\Cabox;
use Zen\Worker\Classes\Convertor;
use Zen\Worker\Classes\Dispatcher;
use Zen\Worker\Classes\Http;
use Zen\Worker\Classes\Core;
use Zen\Worker\Classes\State;
use Zen\Worker\Classes\Stream;
use Zen\Worker\Pools\RiverCrs;
use Zen\Worker\Models\Stream as StreamModel;
use Zen\Worker\Pools\GamaCruises;
use Zen\Worker\Pools\Volga;
use Zen\Worker\Pools\Waterway;
use Zen\Worker\Pools\WaterwayCruises;
use Http as OctoberHttp;

class Debug
{
    # http://azimut.dc/zen/worker/api/debug:testGo
    function testGo()
    {
        Dispatcher::run();

//        $dateStart = Carbon::createFromTimestamp(1717793880)
//            ->setTimeZone('Europe/Moscow')
//            ->format('Y-m-d H:i:s');
//
//        dd($dateStart);


//        $infoflot_stream = StreamModel::find(6);
//        $stream = new Stream($infoflot_stream);

//        (new \Zen\Worker\Pools\WaterwayCruises())->addCruise([
//            'cruise_id' => 17857
//        ]);

//        (new \Zen\Worker\Pools\InfoflotCruises())->addCruise([
//            'data' => [
//                'id' => 17857
//            ]
//        ]);

//        $response = OctoberHttp::get('https://restapi.infoflot.com/cruises/405867/cabins?key=b5262f5d8de5be65b201bb5e3f5e544a245b6082');
//        dd(
//            json_decode(
//                $response->body,
//                1
//            )
//        );

       //Dispatcher::run();
        //$streams = StreamModel::active()->get();
        //Stream::run($streams[0]);

//        $stream = new Stream();
//        $stream->model = StreamModel::find(6);
//        $stream->state = new State($stream);
//        $stream->cache = new Cabox('worker');
//        $stream->work();
    }

    # http://azimut.dc/zen/worker/api/debug:debugMethod
    function debugMethod()
    {
        # 22275
        $http = new Http;
        #$http_query = $http->query('https://gama-nn.ru/execute/navigation', 'xml')->response;
        $http_query = $http->query('https://gama-nn.ru/execute/way/22275/all', 'xml')->response;
        dd($http_query);
    }

    # http://azimut.dc/zen/worker/api/debug:testInfoflotShips
    function testInfoflotShips()
    {
        $http = new Http;
        $http_query = $http->setTimout(120);
        $http_query->dataGet([
            'key' => 'b5262f5d8de5be65b201bb5e3f5e544a245b6082',
            'page'=> 1,
            'limit' => 100
        ]);
        $http_query->query('https://restapi.infoflot.com/ships');
        $response = $http_query->response;
        dd($response['data'][0]);
        //$this->ddd($response['data'][0]);
    }

    # http://azimut.dc/zen/worker/api/debug:testInfoflotShipCruises
    function testInfoflotShipCruises()
    {
        $http = new Http;
        $http_query = $http->setTimout(120);
        $http_query->dataGet([
            'key' => 'b5262f5d8de5be65b201bb5e3f5e544a245b6082',
            'ship'=> 2,
            'page' => 4,
            'date' => date('Y-m-d'),
            'limit' => 500
        ]);
        $http_query->query('https://restapi.infoflot.com/cruises');
        $response = $http_query->response;
        $this->ddd($response);
    }

    # http://azimut.dc/zen/worker/api/debug:testWaterway
    function testWaterway()
    {
        //app('Zen\Worker\Pools\WaterwayCruises')->addCruise(['index' => 14257]);
        $waterway = new WaterwayCruises();
        //$cabox = new Cabox('worker');
        //$cruises = $cabox->get('waterway.cruises');
        //$cruises = $cruises['result']['data'];

        $waterway->getCruises();


        dd('END');
        foreach ($cruises as $cruise) {
            if ($cruise['id'] === 14257) {
                break;
            }
        }

        //dd($cruise);

        $ww_cruise_id = $cruise['id'];

        $ww_cruise = $waterway->wwQuery("json.v3.cruise?id=$ww_cruise_id", null, "waterway.cruise.$ww_cruise_id");
        $ww_cruise = $ww_cruise['result'];

        $waybill = $waterway->wwRoutesHandler($ww_cruise['route']);
        dd($ww_cruise['route'], $waybill);
    }

    # http://azimut.dc/zen/worker/api/debug:testVolga
    public function testVolga()
    {
        $storage_path_short = storage_path('volga-db-short.php');
        $dump_short = Convertor::xmlToArr(file_get_contents($storage_path_short));


        $storage_path_full = storage_path('volga-db.php');
        $dump_full = Convertor::xmlToArr(file_get_contents($storage_path_full));

        $i = 0;
        foreach ($dump_full['cruises']['cruise'] as $cruise) {
            $full_cruise = $cruise['@attributes'];
            $short_cruise = $dump_short['cruises']['cruise'][$i]['@attributes'];

            $id_sync = $full_cruise['id'] === $short_cruise['id'];
            $route_sync = $full_cruise['route'] === $short_cruise['route'];

            if ($id_sync && !$route_sync) {
                dd([
                    'dump_full' => $full_cruise,
                    'dump_short' => $short_cruise
                ]);
            }

            $i++;
        }


        dd('ok');

//        dd([
//            'dump_full' => $dump_full['cruises']['cruise'][0]['@attributes'],
//            'dump_short' => $dump_short['cruises']['cruise'][0]['@attributes']
//        ]);
    }

    # http://azimut.dc/zen/worker/api/debug:testStateLogger
    function testStateLogger()
    {
        $states = Core::getStates();
        dd($states);
    }
}
