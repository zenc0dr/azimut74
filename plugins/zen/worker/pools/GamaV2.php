<?php

namespace Zen\Worker\Pools;

use phpDocumentor\Reflection\Types\Integer;
use Zen\Worker\Classes\Convertor;
use October\Rain\Support\Facades\Http;
use Cache;

class GamaV2 extends RiverCrs
{
    private static string $key = 'gIOZhOWvGDa177aLNh0rofIO';


    /**
     * Диспетчер
     * @return void
     */
    public function runGammaParser()
    {
        //$this->getGamaArchives();
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
            return Convertor::xmlToArr($response->body);
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
        $navigation_data = $this->getGamaFileData('navigation.xml');
        foreach ($navigation_data['NavigationList']['Navigation'] as $navigation) {
            $gama_ship_id = $navigation['@attributes']['ship_id'];
            $gama_ship_name = $navigation['@attributes']['ship_name'];

            # Теплоход
            $ship = $this->getMotorship($gama_ship_name, 'gama', $gama_ship_id);
            if (!$ship) {
                continue;
            }

            # Маршрут $waybill, дата отправления $date_s, дата прибытия $date_f
            $waybill = null;
            foreach ($navigation['RouteList']['Route'] as $route) {
                $gama_short_waybill_ids = $this->getShortWaybillIds($route['@attributes']['name']);
                $date_s = $route['@attributes']['s'];
                $date_f = $route['@attributes']['f'];
                $path_s_id = $route['@attributes']['path_s_id'];
                $path_f_id = $route['@attributes']['path_f_id'];
                $waybill = $this->getGammaWaybill(
                    $navigation['PathList']['Path'],
                    $path_s_id,
                    $path_f_id,
                    $gama_short_waybill_ids
                );
            }

            # Тут сохранили круиз

            $prices = $this->getCruisePrices($navigation['@attributes']['id'], $ship);
            dd($prices);
        }
    }

    public function getCruisePrices(int $gama_cruise_id, $ship): array
    {
        $result = [];

        // Чтение XML-файла навигации по круизу
        $cruise_data = $this->getGamaFileData("navigation_{$gama_cruise_id}_available.xml");

        if (empty($cruise_data['Navigation']['RouteList']['Route'])) {
            return $result;
        }

        $routes = $cruise_data['Navigation']['RouteList']['Route'];

        // Приводим к массиву, если пришёл один Route
        if (isset($routes['@attributes'])) {
            $routes = [$routes];
        }

        $category_prices = [];

        foreach ($routes as $route) {
            $cabins = $route['CabinList']['Cabin'] ?? [];

            // Приводим к массиву, если одна каюта
            if (isset($cabins['@attributes'])) {
                $cabins = [$cabins];
            }

            foreach ($cabins as $cabin) {
                $attrs = $cabin['@attributes'] ?? [];
                $category_name = $attrs['name'] ?? null;
                $category_id = $attrs['id'] ?? null;

                if (!$category_name || !$category_id) {
                    continue;
                }

                // Имя категории в Gama — например "239"
                $cabin_id = $this->getCabinCategoryId($category_name, $ship->id, 'gama');
                if (!$cabin_id) {
                    continue;
                }

                $costs = $cabin['Cost'] ?? [];
                if (isset($costs['@attributes'])) {
                    $costs = [$costs];
                }

                foreach ($costs as $cost) {
                    $cost_attr = $cost['@attributes'] ?? [];
                    $persons = (int)($cost_attr['persons'] ?? 0);
                    if ($persons !== 2) {
                        continue;
                    }

                    $std = isset($cost_attr['std_3']) ? (int)$cost_attr['std_3'] : null;
                    $extra_std = isset($cost_attr['extra_std_3']) ? (int)$cost_attr['extra_std_3'] : null;

                    if (!$std) {
                        continue;
                    }

                    // Сохраняем минимальные цены по каждой категории
                    if (!isset($category_prices[$cabin_id])) {
                        $category_prices[$cabin_id] = [
                            'price_1' => $std,
                            'price_2' => $extra_std,
                        ];
                    } else {
                        $category_prices[$cabin_id]['price_1'] = min($category_prices[$cabin_id]['price_1'], $std);

                        if ($extra_std) {
                            if (!isset($category_prices[$cabin_id]['price_2'])) {
                                $category_prices[$cabin_id]['price_2'] = $extra_std;
                            } else {
                                $category_prices[$cabin_id]['price_2'] = min($category_prices[$cabin_id]['price_2'], $extra_std);
                            }
                        }
                    }
                }
            }
        }

        // Преобразуем в итоговый массив
        foreach ($category_prices as $cabin_id => $prices) {
            $row = ['cabin_id' => $cabin_id];
            $row['price_1'] = $prices['price_1'] ?? 0;
            $row['price_2'] = $prices['price_2'] ?? 0;
            $result[] = $row;
        }

        return $result;
    }




    /**
     * Получить маршрут гаммы
     * @param array $path_list
     * @param int $path_s_id
     * @param int $path_f_id
     * @param array $short_path_ids
     * @return array
     */
    private function getGammaWaybill(
        array $path_list,
        int $path_s_id,
        int $path_f_id,
        array $short_path_ids
    ): array {
        $short_path_ids = array_unique($short_path_ids);
        $items = [];
        $points = 0;
        foreach ($path_list as $item) {
            $path_id = intval($item['@attributes']['id']);
            if ($path_id >= $path_s_id && $path_id <= $path_f_id) {
                if ($path_id === $path_s_id || $path_id === $path_f_id) {
                    $points++;
                }

                $gama_town_name = $item['@attributes']['town_name'];
                $town_id = $this->getTownId($gama_town_name, 'gama');
                $items[] = [
                    'town' => $town_id,
                    'excursion' => '',
                    'bold' => in_array($town_id, $short_path_ids),
                ];
            }
            if ($points == 2) {
                break;
            }
        }
        return $items;
    }
}
