<?php namespace Mcmraak\Rivercrs\Classes\Exist;

use Mcmraak\Rivercrs\Models\Cabins as Cabin;
use Mcmraak\Rivercrs\Classes\Exist;
use Log;
use Zen\Worker\Pools\Waterway as WaterwayPool;

class Waterway extends Exist
{
    public $query_type;

    /**
     * Waterway API json.v3.cabins по умолчанию пагинируется (limit=10),
     * поэтому обязательно собираем все страницы, иначе видим “3 свободных каюты”.
     *
     * @return array<int, array<string, mixed>> список кают из API
     */
    private function fetchAllCabins(WaterwayPool $ww, int $ww_cruise_id, bool $realtime): array
    {
        $limit = 200;
        $offset = 0;
        $count = null;
        $all = [];

        // Защита от бесконечных циклов
        $max_pages = 50;
        $page = 0;

        while (true) {
            $page++;
            if ($page > $max_pages) {
                break;
            }

            $method = "json.v3.cabins?id=$ww_cruise_id&limit=$limit&offset=$offset";
            $cache_key = "waterway.cabins.$ww_cruise_id.$limit.$offset";

            // Передаем $realtime как параметр для обхода кеша при запросах в реальном времени
            $resp = $ww->wwQuery($method, null, $cache_key, $realtime);
            $result = $resp['result'] ?? [];
            $data = $result['data'] ?? [];

            if ($count === null) {
                $count = intval($result['count'] ?? 0);
                if ($count <= 0) {
                    // fallback: если count не пришёл, считаем по текущей странице
                    $count = count($data);
                }
            }

            if ($data) {
                $all = array_merge($all, $data);
            }

            $offset += $limit;

            // Если набрали всё — выходим
            if ($offset >= $count) {
                break;
            }

            // Safety: если страница пустая — выходим
            if (!$data) {
                break;
            }
        }

        return $all;
    }

    /**
     * Получение тарифов/цен по палубам/категориям/размещению.
     * Источник: json.v3.cruise.room-tariffs
     */
    private function fetchRoomTariffs(WaterwayPool $ww, int $ww_cruise_id, bool $realtime): array
    {
        $method = "json.v3.cruise.room-tariffs?id=$ww_cruise_id";
        $cache_key = "waterway.roomtariffs.$ww_cruise_id";
        return $ww->wwQuery($method, null, $cache_key, $realtime);
    }

    public function getExist($checkin, $realtime)
    {
        $this->query_type = ($realtime) ? 'array_now' : 'array';

        $this->checkin = $checkin;

        $ww_cruise_id = $this->checkin->eds_id;

        $ww = new WaterwayPool();
        $ww_rooms = $this->fetchAllCabins($ww, (int)$ww_cruise_id, (bool)$realtime);

        // В realtime для Waterway показываем только базовый тариф (без "Расширенного")
        $tariff_price2 = false;
        $rooms = [];

        foreach ($ww_rooms as $room) {
            if (!$room['availability']) {
                continue;
            }

            // Обрабатываем цены, если они есть
            $price2 = null;
            $price_value = null;
            if (isset($room['minPrice'])) {
                $price2 = $room['minPrice']['discountedPrice'] ?? null;
                $price_value = ($room['minPrice']['basePrice'] ?? 0) / 100;

                if ($price2) {
                    $tariff_price2 = true;
                }
            }

            $record = $this->addRecord([
                'deck_name' => $room['deck']['name'],
                'cabin_name' => $room['class']['name'], # Имя каюты
                'price_places' => $this->getWwPlaces($room['class']['meta_name']), # Кол-во мест
                'price_value' => $price_value, # Цена 1 (может быть null)
                'price2_value' => $price2, # Цена 2 (может быть null)
                'eds' => true
            ]);

            // Добавляем свободную каюту в массив rooms
            // $room['number'] - номер каюты из API
            // $record['deck_id'] - id палубы из addRecord()
            if (isset($room['number']) && isset($record['deck_id'])) {
                $rooms[] = [
                    'n' => $room['number'],
                    'd' => $record['deck_id']
                ];
            }
        }

        return [
            'decks' => $this->records,
            'rooms' => $rooms, # [['n'=> $n, 'd'=> $d],...] - Где n-номер каюты а d это id-палубы
            'tariff_price1_title' => [
                'name' => 'Базовый тариф<br>Руб. на 1 чел.',
                'desc' => '<b>Тариф Базовый.</b><br>Организация питания: завтрак, обед и ужин-буфет организованы по системе «шведский стол», свободная рассадка'
            ],
            'tariff_price2' => $tariff_price2,
            'tariff_price2_title' => [
                'name' => 'Расширенный тариф<br>Руб. на 1 чел.',
                'desc' => '<b>Тариф расширенный.</b><br>Организация питания:<br>▪ завтрак — буфет («шведский стол»);<br>▪ обед «Шеф-Меню» - заказная система (без включенных алкогольных напитков);<br>▪ ужин «Шеф-Меню» - заказная система с включенными напитками (вода, чай, кофе, на выбор: сок, вино красное/белое, пиво). Фиксированная рассадка, количество мест ограничено'
            ],
        ];
    }

    public function addWwRooms(&$rooms, $new_rooms, $deck_id)
    {
        $deck_id = intval($deck_id);
        foreach ($new_rooms as $room) {
            $room = "$room";
            $rooms[] = [
                'n' => $room,
                'd' => $deck_id
            ];
        }
    }

    public function getTariff($data)
    {
        foreach ($data['tariffs'] as $tariff) {
            if ($tariff['tariff_name'] == 'Тариф Взрослый') {
                return $tariff['prices'];
            }
        }
    }

    public function getTariffEx($data)
    {
        foreach ($data['tariffs'] as $tariff) {
            if ($tariff['tariff_name'] == 'Тариф Взрослый расширенный') {
                return $tariff['prices'];
            }
        }
    }

    public function getWwPlaces($string)
    {
        preg_match('/^(\d+)-/', $string, $matches);
        if (isset($matches[1])) {
            return intval($matches[1]);
        }
        return false;
    }

    /**
     * Простой механизм для получения данных о свободных каютах
     * Обходит сложную логику Exist класса и возвращает правильный JSON
     *
     * Логика:
     * 1. Получает начальные данные из кеша (если есть) или формирует из базы - это ВСЕ категории кают с ценами
     * 2. Получает свободные каюты из API Waterway
     * 3. Объединяет их: для категорий со свободными каютами - добавляет номера, для остальных - оставляет "под запрос"
     */
    public function getSimpleExist($checkin, $realtime)
    {
        // Устанавливаем checkin для использования в getCabinCache()
        $this->checkin = $checkin;

        $ww_cruise_id = $checkin->eds_id;
        $ww = new WaterwayPool();

        // Получаем начальные данные из кеша (если есть) - это все категории кают с ценами
        $cabox = new \Zen\Cabox\Classes\Cabox('rivercrs');
        $array_cache_key = "exist_array:{$checkin->id}";
        $initial_data = $cabox->get($array_cache_key, 900); // Получаем из кеша на 15 минут

        // Если нет в кеше, получаем через старый метод (но только для получения структуры decks)
        if (!$initial_data) {
            // Используем старый метод для получения начальной структуры
            $initial_data = $this->getInitialDataFromBase($checkin);
        }

        // Получаем номера кают из базы данных
        $motorship = $checkin->motorship;
        $exist_rooms = json_decode($motorship->exist_rooms ?? '[]', true);

        // Собираем свободные каюты из API, группируя по категории каюты и палубе
        $available_rooms_by_cabin = []; // [cabin_name][deck_id] => [room_numbers]
        $available_rooms_by_number = []; // [room_number] => [cabin_name, deck_id, deck_name]
        $tariff_price2 = false;

        // Получаем ВСЕ категории кают из базы данных через motorship->decksWithCabins()
        $decks_with_cabins = $motorship->decksWithCabins();

        // Создаем карту deck_name => deck_id для быстрого поиска
        $deck_name_to_id_map = [];
        foreach ($decks_with_cabins as $deck_data) {
            $deck = $deck_data['deck'];
            $deck_name_to_id_map[$deck->name] = $deck->id;
        }

        // Создаем карту cabin_name => cabin_id для быстрого поиска по категории каюты
        $cabin_name_to_id_map = [];
        foreach ($decks_with_cabins as $deck_data) {
            $deck = $deck_data['deck'];
            foreach ($deck_data['cabins'] as $cabin) {
                // Используем waterway_name если есть, иначе category
                $cabin_name_key = $cabin->waterway_name ?? $cabin->category ?? '';
                if ($cabin_name_key) {
                    $cabin_name_to_id_map[$cabin_name_key] = $cabin->id;
                }
            }
        }

        // Карта цен из API room-tariffs (дек/категория/места -> цена)
        // Используем её в realtime режиме, т.к. в Waterway цены зависят от палубы и размещения.
        $ww_prices_map = []; // [deck_id][cabin_id][places_qnt] => ['price_value'=>int]
        if ($realtime) {
            try {
                $tariffs_resp = $this->fetchRoomTariffs($ww, (int)$ww_cruise_id, (bool)$realtime);
                foreach (($tariffs_resp['result']['decks'] ?? []) as $ww_deck) {
                    $ww_deck_name = $ww_deck['name'] ?? '';
                    if (!$ww_deck_name) {
                        continue;
                    }

                    // Находим deck_id (в БД имена обычно с "палуба", в API — короткие)
                    $deck_id = $deck_name_to_id_map[$ww_deck_name] ?? null;
                    if (!$deck_id) {
                        $getter = new \Mcmraak\Rivercrs\Classes\Getter();
                        $deck_obj = $getter->getDeck($ww_deck_name);
                        if ($deck_obj) {
                            $deck_id = $deck_obj->id;
                            $deck_name_to_id_map[$ww_deck_name] = $deck_id;
                        }
                    }
                    if (!$deck_id) {
                        continue;
                    }

                    foreach (($ww_deck['roomClasses'] ?? []) as $roomClass) {
                        $cabin_name = $roomClass['name'] ?? '';
                        if (!$cabin_name) {
                            continue;
                        }

                        $cabin_id = $cabin_name_to_id_map[$cabin_name] ?? null;
                        if (!$cabin_id) {
                            $cabin_obj = $this->getCabinCache($cabin_name);
                            if ($cabin_obj) {
                                $cabin_id = $cabin_obj->id;
                                $cabin_name_to_id_map[$cabin_name] = $cabin_id;
                            }
                        }

                        if (!$cabin_id) {
                            continue;
                        }

                        foreach (($roomClass['tariffs'] ?? []) as $tariff) {
                            foreach (($tariff['accommodations'] ?? []) as $acc) {
                                $acc_name = $acc['name'] ?? '';
                                if (!$acc_name) {
                                    continue;
                                }

                                // Базовый тариф
                                $is_base = ($acc_name === 'Тариф Взрослый' || $acc_name === 'Тариф взрослый');
                                if (!$is_base) {
                                    continue;
                                }

                                $places_qnt = intval($acc['id'] ?? 0);
                                if ($places_qnt <= 0) {
                                    continue;
                                }

                                $price_raw = $acc['price']['discountedValue'] ?? ($acc['price']['value'] ?? null);
                                if (!$price_raw) {
                                    continue;
                                }
                                $price_value = intval(floor($price_raw / 100));

                                if (!isset($ww_prices_map[$deck_id])) {
                                    $ww_prices_map[$deck_id] = [];
                                }
                                if (!isset($ww_prices_map[$deck_id][$cabin_id])) {
                                    $ww_prices_map[$deck_id][$cabin_id] = [];
                                }
                                if (!isset($ww_prices_map[$deck_id][$cabin_id][$places_qnt])) {
                                    $ww_prices_map[$deck_id][$cabin_id][$places_qnt] = [
                                        'price_value' => 0,
                                    ];
                                }

                                if ($is_base) {
                                    $ww_prices_map[$deck_id][$cabin_id][$places_qnt]['price_value'] = $price_value;
                                }
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                // В realtime UI лучше показать хоть какие-то цены (fallback на pricing),
                // чем сломать весь endpoint.
                Log::error('Waterway room-tariffs error: ' . $e->getMessage());
            }
        }

        // Получаем данные из API Waterway только если realtime=true
        // При realtime=false показываем только данные из базы (все каюты как "под запрос")
        if ($realtime) {
            $ww_rooms = $this->fetchAllCabins($ww, (int)$ww_cruise_id, (bool)$realtime);

            // Отслеживание уже обработанных номеров кают для дедупликации
            $processed_room_numbers = [];
            foreach ($ww_rooms ?? [] as $room) {
            if ($room['availability'] && isset($room['number'])) {
                $room_number = $room['number'];

                // Пропускаем дубликаты
                if (isset($processed_room_numbers[$room_number])) {
                    continue;
                }
                $processed_room_numbers[$room_number] = true;

                $cabin_name = $room['class']['name'] ?? '';
                $deck_name = $room['deck']['name'] ?? '';

                if (!$cabin_name || !$deck_name) {
                    continue;
                }

                // Ищем deck_id в базе по имени палубы (а не по meta_id из API)
                $deck_id = null;
                if (isset($deck_name_to_id_map[$deck_name])) {
                    $deck_id = $deck_name_to_id_map[$deck_name];
                } else {
                    // Если точного совпадения нет, ищем через getDeck (как в других местах кода)
                    $getter = new \Mcmraak\Rivercrs\Classes\Getter();
                    $deck_obj = $getter->getDeck($deck_name);
                    if ($deck_obj) {
                        $deck_id = $deck_obj->id;
                        // Сохраняем в карту для следующих итераций
                        $deck_name_to_id_map[$deck_name] = $deck_id;
                    }
                }

                if (!$deck_id) {
                    continue; // Не нашли палубу в базе
                }

                // Ищем cabin_id по имени категории каюты
                $cabin_id = $cabin_name_to_id_map[$cabin_name] ?? null;
                if (!$cabin_id) {
                    // Если не нашли по точному совпадению, ищем через getCabinCache
                    $cabin_obj = $this->getCabinCache($cabin_name);
                    if ($cabin_obj) {
                        $cabin_id = $cabin_obj->id;
                        $cabin_name_to_id_map[$cabin_name] = $cabin_id;
                    }
                }

                // Проверяем наличие расширенного тарифа
                if (isset($room['minPrice']['discountedPrice'])) {
                    $tariff_price2 = true;
                }

                // Группируем по категории каюты
                if (!isset($available_rooms_by_cabin[$cabin_name])) {
                    $available_rooms_by_cabin[$cabin_name] = [];
                }
                if (!isset($available_rooms_by_cabin[$cabin_name][$deck_id])) {
                    $available_rooms_by_cabin[$cabin_name][$deck_id] = [];
                }
                $available_rooms_by_cabin[$cabin_name][$deck_id][] = $room_number;

                // Сохраняем для быстрого поиска по номеру
                $available_rooms_by_number[$room_number] = [
                    'cabin_name' => $cabin_name,
                    'cabin_id' => $cabin_id, // Добавляем cabin_id для кают из API
                    'deck_id' => $deck_id, // Теперь это реальный deck_id из базы
                    'deck_name' => $deck_name,
                ];
            }
            }
        }

        // Получаем ВСЕ категории кают из базы данных через motorship->decksWithCabins()
        $decks_with_cabins = $motorship->decksWithCabins();

        // Получаем цены из таблицы pricing для этого checkin
        $pricing_map = [];
        $pricing_data = \DB::table('mcmraak_rivercrs_pricing')
            ->where('checkin_id', $checkin->id)
            ->get();
        foreach ($pricing_data as $price) {
            $pricing_map[$price->cabin_id] = [
                'price_a' => intval($price->price_a ?? 0),
                'price_b' => $price->price_b ? intval($price->price_b) : '',
            ];
        }

        // Формируем decks из данных базы, накладывая информацию о свободных каютах
        $decks_map = [];

        foreach ($decks_with_cabins as $deck_data) {
            $deck = $deck_data['deck'];
            $deck_id = $deck->id;
            $deck_name = $deck->name;

            // Инициализируем палубу, если еще не создана
            if (!isset($decks_map[$deck_id])) {
                $decks_map[$deck_id] = [
                    'id' => $deck_id,
                    'name' => $deck_name,
                    'cabins' => [],
                ];
            }

            // Добавляем категории кают для этой палубы
            foreach ($deck_data['cabins'] as $cabin) {
                $cabin_id = $cabin->id;

                // Пропускаем, если уже добавлена
                if (isset($decks_map[$deck_id]['cabins'][$cabin_id])) {
                    continue;
                }

                // В realtime режиме для Waterway предпочтительно брать цены из API room-tariffs
                // (они учитывают палубу и варианты размещения).
                $prices = [];
                if ($realtime && isset($ww_prices_map[$deck_id][$cabin_id])) {
                    $by_places = $ww_prices_map[$deck_id][$cabin_id];
                    ksort($by_places);
                    foreach ($by_places as $places_qnt => $p) {
                        // Если базовая цена не пришла, пропускаем (иначе будет 0)
                        $pv = intval($p['price_value'] ?? 0);
                        if ($pv <= 0) {
                            continue;
                        }
                        $prices[] = [
                            'price_places' => intval($places_qnt),
                            'price_value' => $pv,
                            'price2_value' => '',
                        ];
                    }
                }

                // Fallback: берем одну цену из pricing (legacy)
                if (!$prices) {
                    $price_a = $pricing_map[$cabin_id]['price_a'] ?? 0;
                    $price_b = $pricing_map[$cabin_id]['price_b'] ?? '';
                    $fallback_places = intval($cabin->places_main_count ?? 2);
                    $prices = [
                        [
                            'price_places' => $fallback_places,
                            'price_value' => $price_a,
                            'price2_value' => $price_b,
                        ]
                    ];
                }

                // main_places: максимум из доступных размещений (иначе UI путается)
                $main_places = 0;
                foreach ($prices as $p) {
                    $main_places = max($main_places, intval($p['price_places'] ?? 0));
                }
                if ($main_places <= 0) {
                    $main_places = intval($cabin->places_main_count ?? 2);
                }

                $decks_map[$deck_id]['cabins'][$cabin_id] = [
                    'id' => $cabin_id,
                    'name' => $cabin->category ?? '',
                    'main_places' => $main_places,
                    'extra_places' => intval($cabin->places_extra_count ?? 0),
                    'prices' => $prices,
                ];
            }
        }

        // Для Waterway всегда строим decks из базы + (realtime) room-tariffs,
        // чтобы не подхватывать потенциально устаревшую структуру из кеша exist_array.
        $decks = [];
        foreach ($decks_map as $deck_id => $deck_data) {
            $deck_data['cabins'] = array_values($deck_data['cabins']);
            $decks[] = $deck_data;
        }

        // Формируем массив rooms: сопоставляем номера из базы с данными из API
        // roomsHandler ожидает все каюты из базы (exist_rooms), но с пометкой о свободных из API
        $rooms = [];
        $processed_room_numbers = []; // Для отслеживания уже обработанных номеров
        
        // Обрабатываем каюты из базы данных (exist_rooms)
        foreach ($exist_rooms as $ev_room) {
            $room_number = $ev_room['n'] ?? '';

            // Пропускаем дубликаты
            if (isset($processed_room_numbers[$room_number])) {
                continue;
            }
            $processed_room_numbers[$room_number] = true;

            $is_free = isset($available_rooms_by_number[$room_number]);

            // Получаем deck_id из API или оставляем 0
            $deck_id = 0;
            $cabin_id = intval($ev_room['c'] ?? 0);
            if ($is_free && isset($available_rooms_by_number[$room_number]['deck_id'])) {
                $deck_id = $available_rooms_by_number[$room_number]['deck_id'];
                // Если cabin_id не указан в базе, но есть в API, используем из API
                if (!$cabin_id && isset($available_rooms_by_number[$room_number]['cabin_id'])) {
                    $cabin_id = $available_rooms_by_number[$room_number]['cabin_id'];
                }
            }

            $rooms[] = [
                'n' => $room_number,
                'c' => $cabin_id,
                'w' => intval($ev_room['w'] ?? 0),
                'h' => intval($ev_room['h'] ?? 0),
                'x' => intval($ev_room['x'] ?? 0),
                'y' => intval($ev_room['y'] ?? 0),
                'f' => $is_free ? 1 : 0, // 1 = свободна, 0 = занята
                'd' => $deck_id,
            ];
        }

        // ДОБАВЛЯЕМ все свободные каюты из API, которых нет в базе данных
        // Это критично для правильного отображения всех доступных кают
        if ($realtime) {
            foreach ($available_rooms_by_number as $room_number => $room_data) {
                // Пропускаем каюты, которые уже обработаны из базы
                if (isset($processed_room_numbers[$room_number])) {
                    continue;
                }

                $cabin_id = $room_data['cabin_id'] ?? 0;
                $deck_id = $room_data['deck_id'] ?? 0;

                // Добавляем каюту, если есть deck_id (cabin_id может быть 0, если категория не найдена в базе)
                if ($deck_id) {
                    $rooms[] = [
                        'n' => $room_number,
                        'c' => $cabin_id, // Может быть 0, если категория не найдена в базе
                        'w' => 0, // Нет данных о размерах для кают из API
                        'h' => 0,
                        'x' => 0,
                        'y' => 0,
                        'f' => 1, // Свободна (из API)
                        'd' => $deck_id,
                    ];
                }
            }
        }

        // Создаем индекс свободных кают по категории и палубе
        $free_rooms_index = [];
        foreach ($rooms as $room) {
            if ($room['f'] == 1) { // Только свободные каюты
                $key = "{$room['c']}_{$room['d']}";
                $free_rooms_index[$key] = true;
            }
        }

        // Добавляем каюты "под запрос" для категорий без свободных кают
        foreach ($decks as $deck) {
            $deck_id = $deck['id'];
            foreach ($deck['cabins'] as $cabin) {
                $cabin_id = $cabin['id'];
                $key = "{$cabin_id}_{$deck_id}";

                // Если нет свободных кают для этой категории на этой палубе
                if (!isset($free_rooms_index[$key])) {
                    $rooms[] = [
                        'n' => 'Под запрос',
                        'c' => $cabin_id,
                        'd' => $deck_id,
                        'w' => 0,
                        'h' => 0,
                        'x' => 0,
                        'y' => 0,
                        'f' => 1, // Свободна (под запрос)
                    ];
                }
            }
        }

        // Добавляем 'eds' => true в каждый cabin (как в Infoflot)
        foreach ($decks as &$deck) {
            foreach ($deck['cabins'] as &$cabin) {
                $cabin['eds'] = true;
            }
        }
        unset($deck, $cabin); // Сбрасываем ссылки

        return [
            'decks' => $decks,
            'rooms' => $rooms,
            'eds' => 'waterway',
            'tariff_price1_title' => $initial_data['tariff_price1_title'] ?? [
                'name' => 'Базовый тариф<br>Руб. на 1 чел.',
                'desc' => '<b>Тариф Базовый.</b><br>Организация питания: завтрак, обед и ужин-буфет организованы по системе «шведский стол», свободная рассадка'
            ],
            // В realtime для Waterway скрываем расширенный тариф
            'tariff_price2' => false,
            'tariff_price2_title' => $initial_data['tariff_price2_title'] ?? [
                'name' => 'Расширенный тариф<br>Руб. на 1 чел.',
                'desc' => '<b>Тариф расширенный.</b><br>Организация питания:<br>▪ завтрак — буфет («шведский стол»);<br>▪ обед «Шеф-Меню» - заказная система (без включенных алкогольных напитков);<br>▪ ужин «Шеф-Меню» - заказная система с включенными напитками (вода, чай, кофе, на выбор: сок, вино красное/белое, пиво). Фиксированная рассадка, количество мест ограничено'
            ],
        ];
    }

    /**
     * Получает начальные данные из базы (все категории кают с ценами)
     * Используется как fallback, если нет данных в кеше
     */
    private function getInitialDataFromBase($checkin)
    {
        $motorship = $checkin->motorship;

        // Получаем ВСЕ категории кают из базы данных через motorship->decksWithCabins()
        $decks_with_cabins = $motorship->decksWithCabins();

        // Получаем цены из таблицы pricing для этого checkin
        $pricing_map = [];
        $pricing_data = \DB::table('mcmraak_rivercrs_pricing')
            ->where('checkin_id', $checkin->id)
            ->get();
        foreach ($pricing_data as $price) {
            $pricing_map[$price->cabin_id] = [
                'price_a' => intval($price->price_a ?? 0),
                'price_b' => $price->price_b ? intval($price->price_b) : '',
            ];
        }

        // Формируем decks
        $decks = [];

        foreach ($decks_with_cabins as $deck_data) {
            $deck = $deck_data['deck'];
            $deck_cabins = [];

            foreach ($deck_data['cabins'] as $cabin) {
                $price_a = $pricing_map[$cabin->id]['price_a'] ?? 0;
                $price_b = $pricing_map[$cabin->id]['price_b'] ?? '';

                $deck_cabins[] = [
                    'id' => $cabin->id,
                    'name' => $cabin->category ?? '',
                    'main_places' => intval($cabin->places_main_count ?? 2),
                    'extra_places' => intval($cabin->places_extra_count ?? 0),
                    'prices' => [
                        [
                            'price_places' => intval($cabin->places_main_count ?? 2),
                            'price_value' => $price_a,
                            'price2_value' => $price_b,
                        ]
                    ],
                ];
            }

            if (!empty($deck_cabins)) {
                $decks[] = [
                    'id' => $deck->id,
                    'name' => $deck->name,
                    'cabins' => $deck_cabins,
                ];
            }
        }

        return [
            'decks' => $decks,
            'tariff_price1_title' => [
                'name' => 'Базовый тариф<br>Руб. на 1 чел.',
                'desc' => '<b>Тариф Базовый.</b><br>Организация питания: завтрак, обед и ужин-буфет организованы по системе «шведский стол», свободная рассадка'
            ],
            'tariff_price2' => false,
            'tariff_price2_title' => [
                'name' => 'Расширенный тариф<br>Руб. на 1 чел.',
                'desc' => '<b>Тариф расширенный.</b><br>Организация питания:<br>▪ завтрак — буфет («шведский стол»);<br>▪ обед «Шеф-Меню» - заказная система (без включенных алкогольных напитков);<br>▪ ужин «Шеф-Меню» - заказная система с включенными напитками (вода, чай, кофе, на выбор: сок, вино красное/белое, пиво). Фиксированная рассадка, количество мест ограничено'
            ],
        ];
    }

}
