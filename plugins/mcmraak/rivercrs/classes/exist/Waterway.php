<?php namespace Mcmraak\Rivercrs\Classes\Exist;

use Mcmraak\Rivercrs\Models\Cabins as Cabin;
use Mcmraak\Rivercrs\Classes\Exist;
use Log;
use Zen\Worker\Pools\Waterway as WaterwayPool;

class Waterway extends Exist
{
    public $query_type;

    public function getExist($checkin, $realtime)
    {
        $this->query_type = ($realtime) ? 'array_now' : 'array';

        $this->checkin = $checkin;

        $ww_cruise_id = $this->checkin->eds_id;

        $ww = new WaterwayPool();
        // Передаем $realtime как параметр для обхода кеша при запросах в реальном времени
        $ww_rooms = $ww->wwQuery("json.v3.cabins?id=$ww_cruise_id", null, "waterway.cabins.$ww_cruise_id", $realtime);

        $tariff_price2 = false;
        $rooms = [];

        foreach ($ww_rooms['result']['data'] as $room) {
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
        
        // Получаем данные из API Waterway (с обходом кеша при realtime)
        $ww_rooms = $ww->wwQuery("json.v3.cabins?id=$ww_cruise_id", null, "waterway.cabins.$ww_cruise_id", $realtime);
        
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
        
        foreach ($ww_rooms['result']['data'] ?? [] as $room) {
            if ($room['availability'] && isset($room['number'])) {
                $cabin_name = $room['class']['name'] ?? '';
                $deck_name = $room['deck']['name'] ?? '';
                $room_number = $room['number'];
                
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
                    'deck_id' => $deck_id, // Теперь это реальный deck_id из базы
                    'deck_name' => $deck_name,
                ];
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
                
                // Получаем цены из pricing или оставляем пустыми
                $price_a = $pricing_map[$cabin_id]['price_a'] ?? 0;
                $price_b = $pricing_map[$cabin_id]['price_b'] ?? '';
                
                $decks_map[$deck_id]['cabins'][$cabin_id] = [
                    'id' => $cabin_id,
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
        }
        
        // Если есть начальные данные из кеша, используем их структуру decks (с ценами)
        if ($initial_data && isset($initial_data['decks'])) {
            $decks = $initial_data['decks']; // Используем начальные данные с ценами
        } else {
            // Преобразуем decks_map в массив (сохраняем порядок кают по ID)
            $decks = [];
            foreach ($decks_map as $deck_id => $deck_data) {
                // Преобразуем cabins из ассоциативного массива в обычный, сохраняя порядок
                $deck_data['cabins'] = array_values($deck_data['cabins']);
                $decks[] = $deck_data;
            }
        }
        
        // Формируем массив rooms: сопоставляем номера из базы с данными из API
        // roomsHandler ожидает все каюты из базы (exist_rooms), но с пометкой о свободных из API
        $rooms = [];
        foreach ($exist_rooms as $ev_room) {
            $room_number = $ev_room['n'] ?? '';
            $is_free = isset($available_rooms_by_number[$room_number]);
            
            // Получаем deck_id из API или оставляем 0
            $deck_id = 0;
            if ($is_free && isset($available_rooms_by_number[$room_number]['deck_id'])) {
                $deck_id = $available_rooms_by_number[$room_number]['deck_id'];
            }
            
            $rooms[] = [
                'n' => $room_number,
                'c' => intval($ev_room['c'] ?? 0),
                'w' => intval($ev_room['w'] ?? 0),
                'h' => intval($ev_room['h'] ?? 0),
                'x' => intval($ev_room['x'] ?? 0),
                'y' => intval($ev_room['y'] ?? 0),
                'f' => $is_free ? 1 : 0, // 1 = свободна, 0 = занята
                'd' => $deck_id,
            ];
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
            'tariff_price2' => $tariff_price2 || ($initial_data['tariff_price2'] ?? false),
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
