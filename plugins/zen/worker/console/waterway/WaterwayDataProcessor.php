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
    private $getter;

    public function __construct($database, $timeout = 30, $limit = null)
    {
        // Убираем ограничение времени выполнения
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('max_input_time', -1);
        
        $this->db = $database;
        $this->apiClient = new WaterwayApiClient($timeout);
        $this->timeout = $timeout;
        $this->limit = $limit;
        $this->getter = new Getter();
    }

    /**
     * Обработка данных о теплоходах
     */
    public function processMotorshipsData()
    {
        ProcessLog::add('Начинаем обработку теплоходов Waterway...');
        
        try {
            $response = $this->apiClient->getMotorships();
            
            if (!is_array($response)) {
                ProcessLog::add("API вернул некорректные данные о теплоходах");
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
            
            if (!empty($ships)) {
                $this->db->saveShipsBatch($ships);
                ProcessLog::add("Сохранено теплоходов в SQLite: " . count($ships));
            }
            
            return $ships;
        } catch (Exception $e) {
            ProcessLog::add("Ошибка при обработке теплоходов: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Обработка круизов и цен
     */
    public function processCruisesData()
    {
        ProcessLog::add('Начинаем обработку круизов Waterway...');
        
        try {
            $cruisesResponse = $this->apiClient->getCruises();
            
            if (!is_array($cruisesResponse)) {
                ProcessLog::add("API вернул некорректные данные о круизах");
                return;
            }
            
            $processedCruises = 0;
            $processedPrices = 0;
            $totalCruises = count($cruisesResponse);
            $cruiseIndex = 0;
            
            // Применяем лимит если указан
            if ($this->limit) {
                $cruisesResponse = array_slice($cruisesResponse, 0, $this->limit, true);
                ProcessLog::add("⚠️  Ограничение парсинга: обрабатываем только {$this->limit} круизов");
            }
            
            foreach ($cruisesResponse as $cruiseId => $cruise) {
                $cruiseIndex++;
                $cruiseIdInt = (int)$cruiseId;
                
                // Показываем прогресс каждые 50 круизов
                if ($cruiseIndex % 50 == 0) {
                    ProcessLog::add("Обработка круиза $cruiseIndex/$totalCruises (ID: $cruiseIdInt)...");
                }
                
                try {
                    // Получаем расписание
                    $routes = $this->apiClient->getCruiseRoute($cruiseIdInt);
                    
                    // Получаем цены
                    $pricesData = $this->apiClient->getCruisePrices($cruiseIdInt);
                    
                    if (!$pricesData || !isset($pricesData['tariffs'])) {
                        ProcessLog::add("Круиз $cruiseIdInt пропущен - нет данных о ценах");
                        continue;
                    }
                    
                    // Обрабатываем расписание и формируем данные круиза
                    $cruiseData = $this->processCruiseData($cruise, $cruiseIdInt, $routes);
                    
                    if ($cruiseData) {
                        $this->db->saveCruise($cruiseData);
                        $processedCruises++;
                    }
                    
                    // Обрабатываем цены
                    $prices = $this->processPricesData($cruiseIdInt, $pricesData);
                    if (!empty($prices)) {
                        $this->db->savePricesBatch($prices);
                        $processedPrices += count($prices);
                    }
                    
                } catch (Exception $e) {
                    ProcessLog::add("Ошибка при обработке круиза $cruiseIdInt: " . $e->getMessage());
                    continue;
                }
            }
            
            ProcessLog::add("=== ИТОГИ ОБРАБОТКИ КРУИЗОВ ===");
            ProcessLog::add("Обработано круизов: $processedCruises");
            ProcessLog::add("Обработано цен: $processedPrices");
            
        } catch (Exception $e) {
            ProcessLog::add("⚠️  Ошибка при получении списка круизов: " . $e->getMessage());
            ProcessLog::add("Продолжаем обработку уже полученных круизов...");
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
        $dateStop = $cruise['dateStop'] ?? null;
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
            $scheduleHtml = $this->processScheduleData($routes, $dateStart);
            
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
     * Формирование HTML расписания (аналог wwGraph)
     */
    private function processScheduleData($routes, $dateStart)
    {
        if (empty($routes) || !is_array($routes)) {
            return '';
        }
        
        $days_of_week = ['вс', 'пн', 'вт', 'ср', 'чт', 'пт', 'сб'];
        $return = [];
        $return[] = '<table><tbody>';
        $return[] = "<tr><td>День</td><td>Стоянка</td><td>Программа дня</td></tr>";
        
        foreach ($routes as $route) {
            if (!isset($route['day']) || !isset($route['portName'])) {
                continue;
            }
            
            $date = Carbon::parse($dateStart);
            $day = $route['day'];
            $port = $route['portName'];
            $excursion = $route['excursion'] ?? '';
            $time_start = $route['timeStart'] ?? '00:00:00';
            $time_stop = $route['timeStop'] ?? '00:00:00';
            $time = $this->formatScheduleTime($time_start, $time_stop);
            $ex_date = $date->addDays(intval($day) - 1);
            $day_of_week = $days_of_week[$ex_date->dayOfWeek];
            $ex_date = $ex_date->format('d.m.Y');
            $return[] = "<tr><td>$day <br>$ex_date<br>$time ($day_of_week)</td><td>$port</td><td>$excursion</td></tr>";
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
        $dateStop = $cruise['dateStop'] ?? null;
        
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
    private function processPricesData($cruiseId, $pricesData)
    {
        $prices = [];
        
        if (!isset($pricesData['tariffs']) || !is_array($pricesData['tariffs'])) {
            return $prices;
        }
        
        foreach ($pricesData['tariffs'] as $tariff) {
            $tariffName = $tariff['tariff_name'] ?? '';
            
            // Обрабатываем только "Тариф Взрослый"
            if ($tariffName !== 'Тариф Взрослый') {
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
                
                $prices[] = [
                    'cruise_id' => $cruiseId,
                    'cabin_category_name' => $price['rt_name'] ?? '',
                    'cabin_category_desc' => $price['rp_name'] ?? null,
                    'deck_name' => $price['deck_name'] ?? null,
                    'price_value' => $priceValue,
                    'tariff_name' => $tariffName
                ];
            }
        }
        
        return $prices;
    }
}

