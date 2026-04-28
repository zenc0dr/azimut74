<?php namespace Zen\Worker\Console\waterway;

use Carbon\Carbon;
use Exception;
use Mcmraak\Rivercrs\Classes\Getter;
use Zen\Worker\Classes\ProcessLog;

class WaterwayDataProcessor
{
    private $db;
    private $apiClient;
    private $timeout;
    private $limit;
    private $limitShips;
    private $limitCruises;
    private $limitCruisesPerShip;
    private $getter;
    private $allowedShipIds = null;
    private $command = null;
    private $progressEvery = 1;
    private ?int $onlyCruiseId = null;

    public function __construct(
        $database,
        $timeout = 30,
        $limit = null,
        $limitShips = null,
        $limitCruises = null,
        $limitCruisesPerShip = null
    )
    {
        // Убираем ограничение времени выполнения
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('max_input_time', -1);
        
        $this->db = $database;
        $this->apiClient = new WaterwayApiClient($timeout);
        $this->timeout = $timeout;
        $this->limit = $limit;
        $this->limitShips = $limitShips ? (int)$limitShips : null;
        $this->limitCruises = $limitCruises ? (int)$limitCruises : null;
        $this->limitCruisesPerShip = $limitCruisesPerShip ? (int)$limitCruisesPerShip : null;
        $this->getter = new Getter();
    }

    /**
     * Подключить вывод в консоль (для долгих прогонов)
     */
    public function setCommand($command)
    {
        $this->command = $command;
        // пробрасываем в API клиент, чтобы видеть прогресс получения списка круизов
        $this->apiClient->setCommand($command);
        return $this;
    }

    /**
     * Частота вывода прогресса по круизам: 1 = каждый круиз, 10 = каждый 10-й и т.д.
     */
    public function setProgressEvery(int $n)
    {
        $this->progressEvery = max(1, $n);
        return $this;
    }

    /**
     * Ограничить обработку одним круизом Waterway (по ID источника).
     * Полезно для отладки/точечного перепарсинга.
     */
    public function setOnlyCruiseId(?int $cruiseId)
    {
        $this->onlyCruiseId = $cruiseId ? (int)$cruiseId : null;
        return $this;
    }

    private function mapCruiseDetailToListItem(array $detail): array
    {
        return [
            'name' => $detail['name'] ?? '',
            'motorshipId' => (int)($detail['motorship']['id'] ?? 0),
            'dateStart' => $detail['dateStart'] ?? null,
            // В list API поле называется dateStop, в cruise API — dateEnd
            'dateStop' => $detail['dateEnd'] ?? null,
            // days (длительность) в API — duration
            'days' => (int)($detail['duration'] ?? 0),
            // В list API было classDescription — берем description если есть
            'classDescription' => $detail['description'] ?? null,
        ];
    }

    private function consoleLine(string $message)
    {
        if ($this->command && method_exists($this->command, 'line')) {
            $this->command->line('[' . date('H:i:s') . '] ' . $message);
        }
    }

    /**
     * Обработка данных о теплоходах
     */
    public function processMotorshipsData()
    {
        ProcessLog::add('Начинаем обработку теплоходов Waterway...');
        $this->consoleLine('Запрос списка теплоходов...');
        
        try {
            $response = $this->apiClient->getMotorships();
            
            if (!is_array($response)) {
                ProcessLog::add("API вернул некорректные данные о теплоходах");
                $this->consoleLine("⚠️  Некорректные данные о теплоходах (API)");
                return [];
            }
            
            $ships = [];
            foreach ($response as $id => $ship) {
                $ships[] = [
                    'id' => (int)$id,
                    'name' => $ship['name'] ?? '',
                    'type' => $ship['type'] ?? null,
                    'description' => $ship['description'] ?? ''
                ];
            }

            // Ограничение по теплоходам (для безопасной отладки / прогрева кеша)
            if ($this->limitShips) {
                $ships = array_slice($ships, 0, $this->limitShips);
                $this->allowedShipIds = array_map(function ($s) {
                    return (int)$s['id'];
                }, $ships);
                ProcessLog::add("⚠️  Ограничение парсинга: обрабатываем только {$this->limitShips} теплоходов");
                $this->consoleLine("Ограничение: {$this->limitShips} теплоходов (IDs: " . implode(',', $this->allowedShipIds) . ")");
            } else {
                $this->allowedShipIds = null;
            }
            
            if (!empty($ships)) {
                $this->db->saveShipsBatch($ships);
                ProcessLog::add("Сохранено теплоходов в SQLite: " . count($ships));
                $this->consoleLine("Сохранено теплоходов в SQLite: " . count($ships));
            }
            
            return $ships;
        } catch (Exception $e) {
            ProcessLog::add("Ошибка при обработке теплоходов: " . $e->getMessage());
            $this->consoleLine("❌ Ошибка обработки теплоходов: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Обработка круизов и цен
     */
    public function processCruisesData()
    {
        ProcessLog::add('Начинаем обработку круизов Waterway...');
        $this->consoleLine('Получаем список круизов (может занять время)...');
        
        try {
            // Точечный режим: один круиз по ID (без загрузки всего списка)
            if ($this->onlyCruiseId) {
                $cruiseIdInt = (int)$this->onlyCruiseId;
                $this->consoleLine("Точечный режим: круиз id=$cruiseIdInt");

                $detail = $this->apiClient->getCruiseById($cruiseIdInt);
                if (!$detail) {
                    ProcessLog::add("⚠️  Не удалось получить круиз $cruiseIdInt через json.v3.cruise");
                    $this->consoleLine("⚠️  Не удалось получить круиз id=$cruiseIdInt");
                    return;
                }
                $cruisesResponse = [
                    $cruiseIdInt => $this->mapCruiseDetailToListItem($detail)
                ];
            } else {
                $cruisesResponse = $this->apiClient->getCruises();
            }
            
            if (!is_array($cruisesResponse)) {
                ProcessLog::add("API вернул некорректные данные о круизах");
                $this->consoleLine("⚠️  Некорректные данные о круизах (API)");
                return;
            }
            
            $processedCruises = 0;
            $processedPrices = 0;
            $totalCruises = count($cruisesResponse);
            $cruiseIndex = 0;
            
            // Фильтр по теплоходам, если задан limitShips
            if (is_array($this->allowedShipIds) && !empty($this->allowedShipIds)) {
                $filtered = [];
                foreach ($cruisesResponse as $cruiseId => $cruise) {
                    $shipId = (int)($cruise['motorshipId'] ?? 0);
                    if ($shipId && in_array($shipId, $this->allowedShipIds, true)) {
                        $filtered[$cruiseId] = $cruise;
                    }
                }
                $cruisesResponse = $filtered;
                $totalCruises = count($cruisesResponse);
                ProcessLog::add("⚠️  Ограничение парсинга: круизы только для выбранных теплоходов (найдено: {$totalCruises})");
            }

            // Ограничение \"N круизов на теплоход\" (для прогрева кеша без долгого прогона)
            if ($this->limitCruisesPerShip) {
                $filtered = [];
                $perShip = [];
                foreach ($cruisesResponse as $cruiseId => $cruise) {
                    $shipId = (int)($cruise['motorshipId'] ?? 0);
                    if (!$shipId) {
                        continue;
                    }
                    if (!isset($perShip[$shipId])) {
                        $perShip[$shipId] = 0;
                    }
                    if ($perShip[$shipId] >= $this->limitCruisesPerShip) {
                        continue;
                    }
                    $perShip[$shipId]++;
                    $filtered[$cruiseId] = $cruise;
                }
                $cruisesResponse = $filtered;
                $totalCruises = count($cruisesResponse);
                ProcessLog::add("⚠️  Ограничение парсинга: максимум {$this->limitCruisesPerShip} круизов на теплоход (итого: {$totalCruises})");
            }

            // Применяем общий лимит если указан (совместимость со старым --limit)
            if ($this->limit) {
                $cruisesResponse = array_slice($cruisesResponse, 0, $this->limit, true);
                ProcessLog::add("⚠️  Ограничение парсинга: обрабатываем только {$this->limit} круизов");
                $totalCruises = count($cruisesResponse);
            }

            // Явный лимит круизов (новый флаг, не конфликтует со старым)
            if ($this->limitCruises) {
                $cruisesResponse = array_slice($cruisesResponse, 0, $this->limitCruises, true);
                ProcessLog::add("⚠️  Ограничение парсинга: обрабатываем только {$this->limitCruises} круизов (limit_cruises)");
                $totalCruises = count($cruisesResponse);
            }
            
            foreach ($cruisesResponse as $cruiseId => $cruise) {
                $cruiseIndex++;
                $cruiseIdInt = (int)$cruiseId;
                $shipId = (int)($cruise['motorshipId'] ?? 0);
                
                // Прогресс в консоль (по умолчанию — каждый круиз)
                if ($cruiseIndex === 1 || $cruiseIndex === $totalCruises || ($cruiseIndex % $this->progressEvery) === 0) {
                    $this->consoleLine("Круиз $cruiseIndex/$totalCruises: id=$cruiseIdInt, ship_id=$shipId — старт");
                }
                
                try {
                    // Получаем расписание
                    $routes = $this->apiClient->getCruiseRoute($cruiseIdInt);
                    
                    // Получаем цены
                    $pricesData = $this->apiClient->getCruisePrices($cruiseIdInt);
                    
                    if (!$pricesData || !isset($pricesData['tariffs'])) {
                        ProcessLog::add("Круиз $cruiseIdInt пропущен - нет данных о ценах");
                        $this->consoleLine("Круиз id=$cruiseIdInt пропущен: нет цен");
                        continue;
                    }
                    
                    // Обрабатываем расписание и формируем данные круиза
                    $cruiseData = $this->processCruiseData($cruise, $cruiseIdInt, $routes);
                    
                    if ($cruiseData) {
                        $this->db->saveCruise($cruiseData);
                        $processedCruises++;
                    }
                    
                    // Обрабатываем цены (передаем ship_id для категорий)
                    $prices = $this->processPricesData($cruiseIdInt, $pricesData, $shipId);
                    if (!empty($prices)) {
                        $this->db->savePricesBatch($prices);
                        $processedPrices += count($prices);
                    }
                    
                    if ($cruiseIndex === 1 || $cruiseIndex === $totalCruises || ($cruiseIndex % $this->progressEvery) === 0) {
                        $this->consoleLine("Круиз $cruiseIndex/$totalCruises: id=$cruiseIdInt — ok (цены: " . count($prices) . ")");
                    }
                    
                } catch (Exception $e) {
                    ProcessLog::add("Ошибка при обработке круиза $cruiseIdInt: " . $e->getMessage());
                    $this->consoleLine("❌ Ошибка круиза id=$cruiseIdInt: " . $e->getMessage());
                    continue;
                }
            }
            
            ProcessLog::add("=== ИТОГИ ОБРАБОТКИ КРУИЗОВ ===");
            ProcessLog::add("Обработано круизов: $processedCruises");
            ProcessLog::add("Обработано цен: $processedPrices");
            $this->consoleLine("Итоги: круизов=$processedCruises, цен=$processedPrices");
            
        } catch (Exception $e) {
            ProcessLog::add("⚠️  Ошибка при получении списка круизов: " . $e->getMessage());
            ProcessLog::add("Продолжаем обработку уже полученных круизов...");
            $this->consoleLine("⚠️  Ошибка списка круизов: " . $e->getMessage());
            // Не выбрасываем исключение, чтобы парсер мог продолжить работу с уже полученными данными
            // Если круизов нет, просто завершаем без ошибки
        }
    }

    /**
     * Обработка данных круиза
     */
    private function processCruiseData($cruise, $cruiseId, $routes)
    {
        $shipId = (int)($cruise['motorshipId'] ?? 0);
        if (!$shipId) {
            ProcessLog::add("Круиз $cruiseId пропущен - отсутствует motorshipId");
            return null;
        }
        
        $name = $cruise['name'] ?? '';
        $dateStart = $cruise['dateStart'] ?? null;
        $dateStop = $cruise['dateStop'] ?? $cruise['dateEnd'] ?? null;
        $days = (int)($cruise['days'] ?? 0);
        $description = $cruise['classDescription'] ?? null;
        
        // Извлекаем маршрут из названия
        $route = $this->extractRouteFromName($name);
        
        // Обрабатываем расписание
        $scheduleHtml = '';
        $waybillData = null;
        $dateStartPrecise = null;
        $dateEndPrecise = null;
        
        // Проверяем, что routes - это массив элементов расписания (не объект с ошибкой)
        $hasValidRoutes = $routes && is_array($routes) && !isset($routes['error']) && !empty($routes);
        
        if ($hasValidRoutes) {
            // Формируем HTML расписание
            $scheduleHtml = $this->processScheduleData($routes, $dateStart, $dateStop);
            
            // Формируем waybill
            $waybillData = $this->processWaybillData($cruise, $routes);
            
            // Обрабатываем точные даты
            $dates = $this->processDates($routes, $dateStart, $dateStop);
            $dateStartPrecise = $dates['date_start'];
            $dateEndPrecise = $dates['date_end'];
        } else {
            // Минимальная информация, если нет расписания
            $minimalInfo = $this->processMinimalInfo($cruise);
            if ($minimalInfo) {
                $waybillData = $minimalInfo['waybill'];
                $dateStartPrecise = $minimalInfo['date_start'];
                $dateEndPrecise = $minimalInfo['date_end'];
            }
        }
        
        return [
            'waterway_cruise_id' => $cruiseId,
            'waterway_ship_id' => $shipId,
            'name' => $name,
            'route' => $route,
            'date_start' => $dateStart ? ($dateStart . ' 00:00:00') : null,
            'date_end' => $dateStop ? ($dateStop . ' 00:00:00') : null,
            'date_start_precise' => $dateStartPrecise,
            'date_end_precise' => $dateEndPrecise,
            'days' => $days,
            'description' => $description,
            'schedule_html' => $scheduleHtml,
            'waybill_data' => $waybillData ? json_encode($waybillData, JSON_UNESCAPED_UNICODE) : null
        ];
    }

    /**
     * Извлечение маршрута из названия круиза
     */
    private function extractRouteFromName($name)
    {
        if (empty($name)) {
            return null;
        }
        
        // Убираем информацию в скобках (например, "(2 дня)")
        $route = preg_replace('/\s*\([^)]+\)\s*/u', '', $name);
        return trim($route);
    }

    /**
     * Формирование HTML расписания (аналог wwGraph).
     * Календарная дата строки: приоритет calendarDate из точки маршрута API (in),
     * иначе dateStart + (day − 1). Не позже официальной даты окончания круиза (dateStop).
     */
    private function processScheduleData($routes, $dateStart, $dateStop = null)
    {
        if (empty($routes) || !is_array($routes)) {
            return '';
        }
        
        $days_of_week = ['вс', 'пн', 'вт', 'ср', 'чт', 'пт', 'сб'];
        $endCap = $dateStop ? Carbon::parse($dateStop)->startOfDay() : null;
        $return = [];
        $return[] = '<table><tbody>';
        $return[] = "<tr><td>День</td><td>Стоянка</td><td>Программа дня</td></tr>";
        
        foreach ($routes as $route) {
            if (!isset($route['day']) || !isset($route['portName'])) {
                continue;
            }
            
            $day = $route['day'];
            $port = $route['portName'];
            $excursion = $route['excursion'] ?? '';
            $time_start = $route['timeStart'] ?? '00:00:00';
            $time_stop = $route['timeStop'] ?? '00:00:00';
            $time = $this->formatScheduleTime($time_start, $time_stop);

            if (!empty($route['calendarDate'])) {
                $ex_date = Carbon::parse($route['calendarDate'])->startOfDay();
            } else {
                $ex_date = Carbon::parse($dateStart)->startOfDay()->addDays(intval($day) - 1);
            }
            if ($endCap && $ex_date->gt($endCap)) {
                $ex_date = $endCap->copy();
            }

            $day_of_week = $days_of_week[$ex_date->dayOfWeek];
            $ex_date_str = $ex_date->format('d.m.Y');
            $return[] = "<tr><td>$day <br>$ex_date_str<br>$time ($day_of_week)</td><td>$port</td><td>$excursion</td></tr>";
        }
        
        $return[] = '</tbody></table>';
        return join("\n", $return);
    }

    /**
     * Форматирование времени для расписания (аналог wwGraphTimeFormat)
     */
    private function formatScheduleTime($timeStart, $timeStop)
    {
        if ($timeStart == '00:00:00') {
            $time_stop = Carbon::parse($timeStop);
            return '<span class="ww_time">Отправление в ' . $time_stop->format('H:i') . '</span>';
        }
        if ($timeStop == '00:00:00') {
            $time_start = Carbon::parse($timeStart);
            return '<span class="ww_time">Прибытие в ' . $time_start->format('H:i') . '</span>';
        }
        
        $time_start = Carbon::parse($timeStart);
        $time_stop = Carbon::parse($timeStop);
        return '<span class="ww_time">' .
            $time_start->format('H:i') .
            ' - ' .
            $time_stop->format('H:i') .
            '</span>';
    }

    /**
     * Формирование waybill (аналог wwRoutesHandler)
     */
    private function processWaybillData($cruise, $routes)
    {
        $waybill = [];
        $alt_routes = explode(' — ', $cruise['name'] ?? '');
        
        // Проверяем, что routes - это массив элементов расписания
        $hasValidRoutes = $routes && is_array($routes) && !isset($routes['error']) && !empty($routes);
        
        if ($hasValidRoutes) {
            $routes_end = count($routes) - 1;
            $routes_i = 0;
            
            foreach ($routes as $route) {
                if (!isset($route['portName'])) {
                    continue;
                }
                
                $townId = $this->getter->getTownId($route['portName'], 'waterway');
                $day = $route['day'] ?? '';
                $excursion = $route['excursion'] ?? '';
                $excursionText = $day ? "[ День: $day ] $excursion" : $excursion;
                
                $bold = (in_array($route['portName'], $alt_routes) || $routes_i == 0 || $routes_i == $routes_end) ? 1 : 0;
                
                $waybill[] = [
                    'town' => $townId,
                    'excursion' => $excursionText,
                    'bold' => $bold
                ];
                $routes_i++;
            }
        } else {
            // Если водоход не даёт маршрут, берём из круиза
            foreach ($alt_routes as $route) {
                $route = trim($route);
                if (empty($route)) {
                    continue;
                }
                
                // Убираем информацию в скобках
                if (strpos($route, ')') > 0) {
                    $route = preg_replace('/\([^()]+\)/', '', $route);
                }
                $route = trim($route);
                
                $townId = $this->getter->getTownId($route, 'waterway');
                $waybill[] = [
                    'town' => $townId,
                    'excursion' => '',
                    'bold' => 0
                ];
            }
            
            // Первый и последний город - bold
            if (count($waybill) > 0) {
                $waybill[0]['bold'] = 1;
                $waybill[count($waybill) - 1]['bold'] = 1;
            }
        }
        
        return count($waybill) >= 2 ? $waybill : null;
    }

    /**
     * Обработка дат (аналог wwDates)
     */
    private function processDates($routes, $dateStart, $dateStop)
    {
        if (empty($routes) || !is_array($routes)) {
            return [
                'date_start' => $dateStart ? ($dateStart . ' 00:00:00') : null,
                'date_end' => $dateStop ? ($dateStop . ' 00:00:00') : null
            ];
        }
        
        $firstRoute = reset($routes);
        $lastRoute = end($routes);
        
        $dateStartPrecise = null;
        $dateEndPrecise = null;
        
        if ($firstRoute && isset($firstRoute['timeStop'])) {
            $dateStartPrecise = $dateStart . ' ' . $firstRoute['timeStop'];
        }
        
        if ($lastRoute && isset($lastRoute['timeStart'])) {
            $dateEndPrecise = $dateStop . ' ' . $lastRoute['timeStart'];
        }
        
        return [
            'date_start' => $dateStartPrecise,
            'date_end' => $dateEndPrecise
        ];
    }

    /**
     * Минимальная информация, если нет расписания (аналог wwMinimalInfo)
     */
    private function processMinimalInfo($cruise)
    {
        $name = $cruise['name'] ?? '';
        $dateStart = $cruise['dateStart'] ?? null;
        $dateStop = $cruise['dateStop'] ?? $cruise['dateEnd'] ?? null;
        
        if (empty($name) || !$dateStart || !$dateStop) {
            return null;
        }
        
        $mini_route = explode(' — ', $name);
        $waybill = [];
        
        foreach ($mini_route as $town_name) {
            if (strpos($town_name, ')') > 0) {
                $town_name = preg_replace('/\([^()]+\)/', '', $town_name);
            }
            $town_name = trim($town_name);
            if (empty($town_name)) {
                continue;
            }
            
            $townId = $this->getter->getTownId($town_name, 'waterway');
            $waybill[] = [
                'town' => $townId,
                'excursion' => '',
                'bold' => 0,
            ];
        }
        
        if (count($waybill) < 2) {
            return null;
        }
        
        $waybill[0]['bold'] = 1;
        $waybill[count($waybill) - 1]['bold'] = 1;
        
        return [
            'waybill' => $waybill,
            'date_start' => $dateStart . ' 00:00:00',
            'date_end' => $dateStop . ' 00:00:00',
        ];
    }

    /**
     * Обработка цен для круиза
     */
    private function processPricesData($cruiseId, $pricesData, $shipId)
    {
        $prices = [];
        $cabinCategories = []; // Собираем уникальные категории кают
        $decks = []; // Собираем уникальные палубы
        $categoryMaxPlaces = []; // [categoryId => max places_qnt]
        
        if (!isset($pricesData['tariffs']) || !is_array($pricesData['tariffs'])) {
            return $prices;
        }
        
        // Сначала собираем все уникальные категории кают и палубы (по обоим тарифам)
        foreach ($pricesData['tariffs'] as $tariff) {
            $tariffName = $tariff['tariff_name'] ?? '';
            
            // Обрабатываем только 2 тарифа:
            // - "Тариф Взрослый" (база)
            // - "Тариф Взрослый расширенный" (будет записан как price_extra)
            $isBase = ($tariffName === 'Тариф Взрослый' || $tariffName === 'Тариф взрослый');
            $isExtended = ($tariffName === 'Тариф Взрослый расширенный');
            if (!$isBase && !$isExtended) {
                continue;
            }
            
            if (!isset($tariff['prices']) || !is_array($tariff['prices'])) {
                continue;
            }
            
            foreach ($tariff['prices'] as $price) {
                $categoryId = $price['rt_id'] ?? null;
                $deckId = $price['deck_id'] ?? null;
                $placesQnt = intval($price['places_qnt'] ?? 1);
                if ($placesQnt <= 0) {
                    $placesQnt = 1;
                }
                
                // Если есть ID категории, добавляем в список для сохранения
                if ($categoryId !== null && !isset($cabinCategories[$categoryId])) {
                    $cabinCategories[$categoryId] = [
                        'id' => $categoryId,
                        'name' => $price['rt_name'] ?? '',
                        'description' => $price['rp_name'] ?? null,
                        'meta_id' => $price['rp_id'] ?? null,
                        'meta_name' => $price['rt_meta_name'] ?? null, // meta_name из roomClass
                        'ship_id' => $shipId,
                        'deck_id' => null, // Оставляем NULL, так как одна категория может быть на разных палубах
                        // ВАЖНО: places будет дорассчитан по max places_qnt
                    ];
                }

                if ($categoryId !== null) {
                    $categoryMaxPlaces[$categoryId] = max(intval($categoryMaxPlaces[$categoryId] ?? 1), $placesQnt);
                }
                
                // Если есть ID палубы, добавляем в список для сохранения
                if ($deckId !== null && !isset($decks[$deckId])) {
                    $decks[$deckId] = [
                        'id' => $deckId,
                        'name' => $price['deck_name'] ?? '',
                        'meta_id' => $price['deck_meta_id'] ?? null,
                        'meta_name' => $price['deck_meta_name'] ?? null,
                        'ship_id' => $shipId
                    ];
                }
            }
        }
        
        // Проставляем places для категорий (максимальное размещение по данным тарифов)
        foreach ($cabinCategories as $categoryId => &$cat) {
            $cat['places'] = intval($categoryMaxPlaces[$categoryId] ?? 1);
        }
        unset($cat);

        // Сохраняем палубы в базу данных
        if (!empty($decks)) {
            try {
                $this->db->saveDecksBatch(array_values($decks));
            } catch (Exception $e) {
                ProcessLog::add("Ошибка при сохранении палуб для круиза $cruiseId: " . $e->getMessage());
            }
        }
        
        // Сохраняем категории кают в базу данных
        if (!empty($cabinCategories)) {
            try {
                $this->db->saveCabinCategoriesBatch(array_values($cabinCategories));
            } catch (Exception $e) {
                ProcessLog::add("Ошибка при сохранении категорий кают для круиза $cruiseId: " . $e->getMessage());
            }
        }
        
        // Теперь обрабатываем цены:
        // - price_value = базовый взрослый
        // - price_extra = расширенный взрослый
        // - places_qnt = тип размещения (1/2/3/...)
        $pricesMap = []; // key: "$categoryId:$deckId:$placesQnt"
        foreach ($pricesData['tariffs'] as $tariff) {
            $tariffName = $tariff['tariff_name'] ?? '';
            
            $isBase = ($tariffName === 'Тариф Взрослый' || $tariffName === 'Тариф взрослый');
            $isExtended = ($tariffName === 'Тариф Взрослый расширенный');
            if (!$isBase && !$isExtended) {
                continue;
            }
            
            if (!isset($tariff['prices']) || !is_array($tariff['prices'])) {
                continue;
            }
            
            foreach ($tariff['prices'] as $price) {
                $priceValue = (int)($price['price_value'] ?? 0);
                if ($priceValue <= 0) {
                    continue;
                }
                
                $categoryId = $price['rt_id'] ?? null;
                $deckId = $price['deck_id'] ?? null;
                $placesQnt = intval($price['places_qnt'] ?? 1);
                if ($placesQnt <= 0) {
                    $placesQnt = 1;
                }

                if ($categoryId === null) {
                    continue;
                }

                $key = $categoryId . ':' . intval($deckId ?? 0) . ':' . $placesQnt;

                if (!isset($pricesMap[$key])) {
                    $pricesMap[$key] = [
                        'cruise_id' => $cruiseId,
                        'cabin_category_id' => $categoryId,
                        'cabin_category_name' => $price['rt_name'] ?? '', // совместимость
                        'cabin_category_desc' => $price['rp_name'] ?? null,
                        'deck_id' => $deckId,
                        'deck_name' => $price['deck_name'] ?? null, // совместимость
                        'price_value' => null,
                        'price_extra' => null,
                        'places_qnt' => $placesQnt,
                        'tariff_name' => 'Тариф Взрослый',
                    ];
                }

                if ($isBase) {
                    $pricesMap[$key]['price_value'] = $priceValue;
                } elseif ($isExtended) {
                    $pricesMap[$key]['price_extra'] = $priceValue;
                }
            }
        }

        // Собираем итоговый массив цен: берем только записи, где есть базовая цена
        foreach ($pricesMap as $row) {
            if (!$row['price_value']) {
                continue;
            }
            $prices[] = $row;
        }
        
        return $prices;
    }
}

