<?php

namespace Zen\Worker\Pools;

use phpDocumentor\Reflection\Types\Integer;
use Zen\Worker\Classes\Convertor;
use October\Rain\Support\Facades\Http;
use Cache;

class GamaV2
{
    private static string $key = 'gIOZhOWvGDa177aLNh0rofIO';


    /**
     * Диспетчер
     * @return void
     */
    public function runGammaParser()
    {
        # $this->getGamaArchive();
        $this->handleCruises();
    }

    /**
     * Делает запрос к Гама и скачивает xml-файлы
     * @return void
     */
    public function getGamaArchives(): void
    {
        $zip_url = 'https://gama-nn.ru/satellite/xml/zip/?key=' . self::$key;
        $storage_path = base_path('storage/gama_arc');
        $zip_file = $storage_path . '/gama.zip';
        master()->files()->chekFileDir($zip_file);
        shell_exec("wget -O " . escapeshellarg($zip_file) . " " . escapeshellarg($zip_url));
        shell_exec("unzip -o " . escapeshellarg($zip_file) . " -d " . escapeshellarg($storage_path));
    }

    /**
     * По идентификатору маршрута получает дополнительные данные
     * @param int $route_id
     * @return mixed
     */
    public function getGamaRouteData(int $route_id)
    {
        $cache_key = "gamma_route:$route_id";
        return Cache::remember($cache_key, 50, function () use ($route_id) {
            $xml_url = "https://gama-nn.ru/satellite/route/$route_id/?key=" . self::$key;
            $response = Http::get($xml_url);
            return $response->body;
        });
    }

    /** Прочитывает содержимое xml-файла и возвращает массив
     * @param string $file_name
     * @return mixed
     */
    public function getGamaFileData(string $file_name)
    {
        return Convertor::xmlToArr(
            file_get_contents(
                base_path("storage/gama_arc/$file_name")
            )
        );
    }

    public function handleCruises()
    {
        $navigation = $this->getGamaFileData('navigation.xml');
        //$navigation = $navigation['NavigationList']['Navigation'];
        dd($navigation);
    }
}
