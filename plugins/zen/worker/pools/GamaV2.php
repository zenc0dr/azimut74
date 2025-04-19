<?php

namespace zen\worker\pools;

use phpDocumentor\Reflection\Types\Integer;
use Zen\Worker\Classes\Convertor;
use October\Rain\Support\Facades\Http;
use Cache;

class GamaV2
{
    private static string $key = 'gIOZhOWvGDa177aLNh0rofIO';

    public function getGamaArchive()
    {
        $zip_url = 'https://gama-nn.ru/satellite/xml/zip/?key=' . self::$key;
        $storage_path = base_path('storage/gama_arc');
        $zip_file = $storage_path . '/gama.zip';
        master()->files()->chekFileDir($zip_file);
        shell_exec("wget -O " . escapeshellarg($zip_file) . " " . escapeshellarg($zip_url));
        shell_exec("unzip -o " . escapeshellarg($zip_file) . " -d " . escapeshellarg($storage_path));
    }

    public function getGamaRouteData(int $route_id)
    {
        $cache_key = "gamma_route:$route_id";
        return Cache::remember($cache_key, 50, function () use ($route_id) {
            $xml_url = "https://gama-nn.ru/satellite/route/$route_id/?key=" . self::$key;
            $response = Http::get($xml_url);
            return $response->body;
        });
    }

    public function gamaCruises()
    {
//        $base = Convertor::xmlToArr(
//            file_get_contents(
//                base_path('storage/gama_arc/dir_generic.xml')
//            )
//        );

        //$xml_url = 'https://gama-nn.ru/satellite/route/30644/?key=gIOZhOWvGDa177aLNh0rofIO';

        $navigation = Convertor::xmlToArr(
            file_get_contents(
                base_path('storage/gama_arc/navigation.xml')
            )
        );

        dd($navigation);

        $navigation = $navigation['NavigationList']['Navigation'];

        foreach ($navigation as $ship_nav) {
            dd($ship_nav);
        }
    }
}
