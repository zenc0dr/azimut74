<?php

namespace Zen\Worker\Pools;

use phpDocumentor\Reflection\Types\Integer;
use Zen\Worker\Classes\Convertor;
use October\Rain\Support\Facades\Http;
use Cache;
use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use View;
use DB;
use Zen\Worker\Classes\ProcessLog;

class GamaV2 extends RiverCrs
{
    private static string $key = 'gIOZhOWvGDa177aLNh0rofIO';
    private static array $stats = [
        'navigations' => [],
        'cruises' => 0,
        'ships' => [],
        'cabins' => 0,
    ];

    /**
     * Диспетчер
     * @return void
     */
    public function runGammaParser()
    {
        $this->getGamaArchives();
        $this->handleCruises();
        //dd(self::$stats);
    }

    /**
     * Делает запрос к Гама и скачивает xml-файлы
     * @return void
     */
    public function getGamaArchives(): void
    {
        ProcessLog::add('Скачивание архивов gama...');
        $zip_url = 'https://gama-nn.ru/satellite/xml/zip/?key=' . self::$key;
        $storage_path = base_path('storage/gama_arc');
        $zip_file = $storage_path . '/gama.zip';
        master()->files()->chekFileDir($zip_file);
        shell_exec("rm -rf " . escapeshellarg($storage_path) . "/*");
        shell_exec("wget -O " . escapeshellarg($zip_file) . " " . escapeshellarg($zip_url));
        shell_exec("unzip -o " . escapeshellarg($zip_file) . " -d " . escapeshellarg($storage_path));
        ProcessLog::add('Архивы скачаны и распакованы');
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
            try {
                $xml_url = "https://gama-nn.ru/satellite/route/$route_id/?key=" . self::$key;
                $response = Http::get($xml_url);
                return Convertor::xmlToArr($response->body);
            } catch (\Throwable $e) {
                return null;
            }
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
        ProcessLog::add('Обработка навигаций');
        $navigation_data = $this->getGamaFileData('navigation.xml');

        foreach ($navigation_data['NavigationList']['Navigation'] as $navigation) {
            $navigation_id = $navigation['@attributes']['id'];
            $gama_ship_id = $navigation['@attributes']['ship_id'];
            $gama_ship_name = $navigation['@attributes']['ship_name'];
            ProcessLog::add("Обработка теплохода $gama_ship_name");

            # Теплоход
            $ship = $this->getMotorship($gama_ship_name, 'gama', $gama_ship_id);
            if (!$ship) {
                ProcessLog::add("Теплоход $gama_ship_name исключён");
                continue;
            }

            # Маршрут $waybill, дата отправления $date_s, дата прибытия $date_f
            $waybill = null;
            foreach ($navigation['RouteList']['Route'] as $route) {
                $cruise_id = $route['@attributes']['id'];

//                if ($cruise_id !== '29440') {
//                    continue;
//                }

                ProcessLog::add("Обработка круиза gama:$cruise_id...");

                $prices = $this->getCruisePrices($navigation_id, $cruise_id, $ship, $gama_ship_id);

                if (!$prices) {
                    ProcessLog::add("Для круиза gama:$cruise_id отсутствуют цены, круиз игнорирован.");
                    continue;
                }

                $gama_short_waybill_ids = $this->getShortWaybillIds($route['@attributes']['name']);
                $date_s = $route['@attributes']['s'];
                $date_f = $route['@attributes']['f'];
                $path_s_id = $route['@attributes']['path_s_id'];
                $path_f_id = $route['@attributes']['path_f_id'];

                # Маршрут
                $waybill = $this->getGammaWaybill(
                    $navigation['PathList']['Path'],
                    $path_s_id,
                    $path_f_id,
                    $gama_short_waybill_ids
                );

                if (!$waybill) {
                    ProcessLog::add("Ошибка данных! --- cruise_id:$cruise_id - Отсутствует маршрут круиз игнорирован.");
                    continue;
                }

                ProcessLog::add("Маршрут получен");

                # Расписание в виде таблицы table
                $html_table = $this->gamaDesignScheduleV2(
                    $navigation['PathList']['Path'],
                    (int)$route['@attributes']['path_s_id'],
                    (int)$route['@attributes']['path_f_id']
                );

                ProcessLog::add("Таблица расписания получена");

                $checkin = Checkin::where('eds_code', 'gama')
                    ->where('eds_id', $cruise_id)
                    ->first();

                if (!$checkin) {
                    $checkin = new Checkin;
                }

                $checkin->date = master()->carbon($date_s)->toDateTimeString();
                $checkin->dateb = master()->carbon($date_f)->toDateTimeString();
                $checkin->desc_1 = $html_table; // Тут строка с html-таблицей
                $checkin->motorship_id = $ship->id; // Идентификатор корабля в таблице mcmraak_rivercrs_motorships
                $checkin->active = 1;
                $checkin->eds_code = 'gama'; // Код источника
                $checkin->eds_id = $cruise_id; // Идентификатор круиза в таблице mcmraak_rivercrs_checkins
                $checkin->waybill_id = $waybill; // Маршрут, описание будет ниже
                $checkin->save();

                $this->fixCheckin($checkin->id);

                ProcessLog::add("Круиз добавлен в базу. Обработка цен...");

                $insert_prices = [];
                foreach ($prices as $price) {
                    $insert_prices[] = [
                        'checkin_id' => $checkin->id,
                        'cabin_id' => $price['cabin_id'],
                        'price_a' => $price['price_1']
                    ];
                }

                DB::table('mcmraak_rivercrs_pricing')
                    ->where('checkin_id', $checkin->id)
                    ->delete();

                DB::table('mcmraak_rivercrs_pricing')
                    ->insert($insert_prices);

                ProcessLog::add("Обработка круиза завершена.");
            }

            self::$stats['navigations'][] = $navigation_id;
        }
    }

    public function getCruisePrices($navigation_id, $gama_cruise_id, $ship, $gama_ship_id): array
    {
        // Чтение XML-файла навигации по круизу
        $cruise_data = $this->getGamaFileData("navigation_{$navigation_id}_available.xml");

        $routes = $cruise_data['Navigation']['RouteList']['Route'];

        // Приводим к массиву, если пришёл один Route
        if (isset($routes['@attributes'])) {
            $routes = [$routes];
        }

        ProcessLog::add("Обработка маршрутов");
        $category_prices = [];

        foreach ($routes as $route) {
            $cruise_id = $route['@attributes']['id'];
            if ($cruise_id !== $gama_cruise_id) {
                continue;
            }

            $cabins = $route['CabinList']['Cabin'] ?? [];

            // Приводим к массиву, если одна каюта
            if (isset($cabins['@attributes'])) {
                $cabins = [$cabins];
            }
            ProcessLog::add("Обработка категорий кают");

            foreach ($cabins as $cabin) {
                $attrs = $cabin['@attributes'] ?? [];
                $cabin_id = $attrs['id'];

                $gama_cabin_category = $this->getGamaCategory($cabin_id, $gama_ship_id);

                $category_name = $gama_cabin_category['name'];
                $deck_name = $gama_cabin_category['deck_name'];
                ProcessLog::add("Обработка категории: $category_name");
                $places = intval($gama_cabin_category['places']);

                if (!$gama_cabin_category['name']) {
                    ProcessLog::add("Ошибка: не найдена категория каюты");
                    continue;
                }

                // Имя категории в Gama — например "239"
                $category_id = $this->getCabinCategoryId($category_name, $ship->id, 'gama', $places);
                if (!$category_id) {
                    ProcessLog::add("Исключение: Категория каюты запрещена.");
                    continue;
                }

                $deck = $this->getDeck($deck_name);
                $this->deckPivotCheck($category_id, $deck->id);

                $costs = $cabin['Cost'] ?? [];
                if (isset($costs['@attributes'])) {
                    $costs = [$costs];
                }

                ProcessLog::add("Получение цен");
                $price_count = 0;
                foreach ($costs as $cost) {
                    $cost_attr = $cost['@attributes'];
                    $persons = (int)($cost_attr['persons'] ?? 0);

//                    file_put_contents(
//                        storage_path('gama_costs.log'),
//                        $cruise_id . '|' . json_encode($cost_attr) . "\n",
//                        FILE_APPEND
//                    );

                    # Пропускаем только двухместные
                    if ($persons !== $places) {
                        continue;
                    }

                    $price = (int) $cost_attr['std_3'] ?? 0;

                    if (!$price) {
                        continue;
                    }

                    ProcessLog::add("Цена [$category_name]: $price");
                    $category_prices[$category_id] = [
                        'cabin_id' => $category_id,
                        'price_1' => $price,
                    ];
                    $price_count++;
                }
                ProcessLog::add("Цен получено: $price_count");
            }
        }

        return array_values($category_prices);
    }

    public function getGamaCategory($cabin_id, $gama_ship_id): array
    {
        $generic_data = $this->getGamaFileData('dir_generic.xml');
        foreach ($generic_data['ShipList']['Ship'] as $ship) {
            $ship_id = $ship['@attributes']['id'];

            if ($ship_id === $gama_ship_id) {
                foreach ($ship['DeckList']['Deck'] as $deck) {
                    $deck_name = $deck['@attributes']['name'];
                    if (!isset($deck['CabinList']['Cabin'])) {
                        ProcessLog::add("Ошибка --- Для палубы $deck_name отсутствуют каюты");
                        continue;
                    }
                    foreach ($deck['CabinList']['Cabin'] as $cabin) {
                        $gama_cabin_id = $cabin['@attributes']['id'];
                        if ($cabin_id === $gama_cabin_id) {
                            $category_id = $cabin['@attributes']['category_id'];
                            $places = $cabin['@attributes']['places'];
                            $category_name = null;
                            foreach ($generic_data['CategoryList']['Category'] as $category) {
                                if ($category['@attributes']['id'] === $category_id) {
                                    $category_name = $category['@attributes']['name'];
                                }
                            }
                            return [
                                'id' => $category_id,
                                'name' => $category_name,
                                'deck_name' => $deck_name,
                                'places' => $places,
                            ];
                        }
                    }
                }
            }
        }
        return [];
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

    public function gamaDesignScheduleV2(array $path_list, int $path_s_id, int $path_f_id): string
    {
        $gama_cruise_route = [];

        foreach ($path_list as $path) {
            $attrs = $path['@attributes'] ?? [];
            $path_id = (int)($attrs['id'] ?? 0);

            // Только точки в пределах круиза
            if ($path_id < $path_s_id || $path_id > $path_f_id) {
                continue;
            }

            $town_name = $attrs['town_name'] ?? '';
            $start_time = $attrs['s'] ?? null;
            $end_time = $attrs['f'] ?? null;

            if (!$town_name || !$start_time || !$end_time) {
                continue;
            }

            $gama_cruise_route[] = [
                'town' => $town_name,
                'start' => date('d.m.Y H:i:s', strtotime($start_time)),
                'end' => date('d.m.Y H:i:s', strtotime($end_time)),
            ];
        }

        $table_data = [];

        foreach ($gama_cruise_route as $item) {
            $town = $item['town'];
            $full_date_1 = master()->carbon($item['start']);
            $full_date_2 = master()->carbon($item['end']);
            $diff_in_days = $full_date_2->diffInDays($full_date_1);
            $stay = $full_date_2->diffInSeconds($full_date_1);
            $stay = gmdate('H:i', $stay);

            if ($diff_in_days === 0) {
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
}
