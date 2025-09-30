<?php namespace Zen\Worker\Console\gama;

use Mcmraak\Rivercrs\Classes\Getter;
use Carbon\Carbon;
use View;
use Exception;
use Zen\Worker\Classes\ProcessLog;

class GamaDataProcessor
{
    private $db;
    private $getter;
    private $apiClient;
    private $timeout;
    private $limit;

    public function __construct($database, $timeout = 30, $limit = null)
    {
        // Убираем ограничение времени выполнения
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('max_input_time', -1);
        
        $this->db = $database;
        $this->getter = new Getter();
        $this->apiClient = new GamaApiClient($timeout);
        $this->timeout = $timeout;
        $this->limit = $limit;
    }

    /**
     * Обработка навигационных данных
     */
    public function processNavigationData()
    {
        $navigationData = $this->apiClient->getNavigationData();
        
        if (!isset($navigationData['NavigationList']['Navigation'])) {
            throw new Exception('Навигационные данные не найдены');
        }

        $navigations = $navigationData['NavigationList']['Navigation'];
        if (isset($navigations['@attributes'])) {
            $navigations = [$navigations];
        }

        foreach ($navigations as $navigation) {
            $navigationId = $navigation['@attributes']['id'];
            $gamaShipId = $navigation['@attributes']['ship_id'];
            $gamaShipName = $navigation['@attributes']['ship_name'];
            
            // Сохраняем теплоход
            $this->db->saveShip($gamaShipId, $gamaShipName);
            
            // Обрабатываем маршруты
            if (isset($navigation['RouteList']['Route'])) {
                $routes = $navigation['RouteList']['Route'];
                if (isset($routes['@attributes'])) {
                    $routes = [$routes];
                }
                
                foreach ($routes as $route) {
                    $this->processRoute($navigation, $route, $gamaShipId, $navigationId);
                }
            }
        }
    }

    /**
     * Обработка маршрута
     */
    private function processRoute($navigation, $route, $gamaShipId, $navigationId)
    {
        $cruiseId = $route['@attributes']['id'];
        $routeName = $route['@attributes']['name'];
        $dateStart = $route['@attributes']['s'];
        $dateEnd = $route['@attributes']['f'];
        $pathSId = $route['@attributes']['path_s_id'];
        $pathFId = $route['@attributes']['path_f_id'];
        
        // Получаем маршрут
        $waybill = $this->getGammaWaybill(
            $navigation['PathList']['Path'],
            $pathSId,
            $pathFId,
            $this->getShortWaybillIds($routeName)
        );
        
        // Получаем расписание
        $scheduleHtml = $this->gamaDesignScheduleV2(
            $navigation['PathList']['Path'],
            $pathSId,
            $pathFId
        );
        
        // Сохраняем круиз
        $cruiseData = [
            'gama_cruise_id' => $cruiseId,
            'gama_ship_id' => $gamaShipId,
            'name' => $routeName,
            'route_name' => $routeName,
            'date_start' => $dateStart,
            'date_end' => $dateEnd,
            'path_s_id' => $pathSId,
            'path_f_id' => $pathFId,
            'waybill_data' => json_encode($waybill),
            'schedule_html' => $scheduleHtml
        ];
        
        $this->db->saveCruise($cruiseData);
        
        // Сохраняем путевой лист
        $this->saveWaybill($cruiseId, $waybill);
    }

    /**
     * Обработка данных о теплоходах и каютах
     */
    public function processShipsData()
    {
        $shipsData = $this->apiClient->getGenericData();
        
        if (!isset($shipsData['ShipList']['Ship'])) {
            return;
        }
        
        $ships = $shipsData['ShipList']['Ship'];
        if (isset($ships['@attributes'])) {
            $ships = [$ships];
        }
        
        foreach ($ships as $ship) {
            $gamaShipId = $ship['@attributes']['id'];
            $shipName = $ship['@attributes']['name'];
            
            // Обновляем описание теплохода
            $this->db->saveShip($gamaShipId, $shipName, '');
            
            // Обрабатываем палубы и каюты
            if (isset($ship['DeckList']['Deck'])) {
                $decks = $ship['DeckList']['Deck'];
                if (isset($decks['@attributes'])) {
                    $decks = [$decks];
                }
                
                foreach ($decks as $deck) {
                    $gamaDeckId = $deck['@attributes']['id'];
                    $deckName = $deck['@attributes']['name'];
                    
                    $this->db->saveDeck($gamaDeckId, $deckName, $gamaShipId);
                    
                    // Обрабатываем категории кают
                    if (isset($deck['CabinList']['Cabin'])) {
                        $cabins = $deck['CabinList']['Cabin'];
                        if (isset($cabins['@attributes'])) {
                            $cabins = [$cabins];
                        }
                        
                        foreach ($cabins as $cabin) {
                            $this->processCabinCategory($cabin, $gamaDeckId, $gamaShipId, $shipsData);
                        }
                    }
                }
            }
        }
    }

    /**
     * Обработка категории кают
     */
    private function processCabinCategory($cabin, $gamaDeckId, $gamaShipId, $genericData)
    {
        $gamaCabinId = $cabin['@attributes']['id'];
        $categoryId = $cabin['@attributes']['category_id'];
        $places = $cabin['@attributes']['places'];
        
        // Получаем название категории из справочника
        $categoryName = $this->getCategoryName($categoryId, $genericData);
        
        if ($categoryName) {
            $this->db->saveCabinCategory(
                $categoryId,
                $categoryName,
                $places,
                $gamaDeckId,
                $gamaShipId
            );
        }
    }

    /**
     * Получение названия категории из справочника
     */
    private function getCategoryName($categoryId, $genericData)
    {
        if (!isset($genericData['CategoryList']['Category'])) {
            return null;
        }
        
        $categories = $genericData['CategoryList']['Category'];
        if (isset($categories['@attributes'])) {
            $categories = [$categories];
        }
        
        foreach ($categories as $category) {
            if ($category['@attributes']['id'] == $categoryId) {
                return $category['@attributes']['name'];
            }
        }
        
        return null;
    }

    /**
     * Обработка круизов и цен (упрощенная версия)
     */
    public function processCruisesData()
    {
        ProcessLog::add("🎫 Обработка круизов и цен...");
        
        // Получаем все круизы из базы
        $cruises = $this->db->getAllCruises();
        
        // Применяем лимит если указан
        if ($this->limit) {
            $cruises = array_slice($cruises, 0, $this->limit);
            ProcessLog::add("⚠️  Ограничение парсинга: обрабатываем только {$this->limit} круизов");
        }
        
        $processed = 0;
        $totalCruises = count($cruises);
        
        ProcessLog::add("📊 Найдено круизов для обработки цен: $totalCruises");
        
        foreach ($cruises as $cruise) {
            $this->processCruisePricesPrivate($cruise);
            $processed++;
            
            // Логируем каждые 50 круизов
            if ($processed % 50 == 0) {
                ProcessLog::add("📊 Обработано круизов: $processed из $totalCruises");
                set_time_limit(0);
                ini_set('max_execution_time', 0);
            }
        }
        
        ProcessLog::add("✅ Обработка круизов завершена: $processed из $totalCruises");
    }

    /**
     * Обработка цен одного круиза (для внешнего вызова)
     */
    public function processCruisePrices($cruise)
    {
        $this->processCruisePricesPrivate($cruise);
    }

    /**
     * Обработка цен круиза через API в реальном времени
     */
    private function processCruisePricesPrivate($cruise)
    {
        try {
            // Сбрасываем время выполнения перед каждым запросом
            set_time_limit(0);
            ini_set('max_execution_time', 0);
            
            // Получаем данные о ценах через API
            $routeData = $this->apiClient->getGamaRouteData($cruise['gama_cruise_id']);
            
            if (!isset($routeData['Route']['CabinList']['Cabin'])) {
                ProcessLog::add("Нет данных о каютах для круиза {$cruise['gama_cruise_id']}");
                return;
            }
            
            $cabins = $routeData['Route']['CabinList']['Cabin'];
            if (isset($cabins['@attributes'])) {
                $cabins = [$cabins];
            }
            
            $pricesProcessed = 0;
            foreach ($cabins as $cabin) {
                $pricesProcessed += $this->processCabinPricesFromApi($cabin, $cruise['id'], $cruise['gama_ship_id']);
            }
            
            if ($pricesProcessed > 0) {
                ProcessLog::add("Обработано цен для круиза {$cruise['gama_cruise_id']}: $pricesProcessed");
            }
            
        } catch (Exception $e) {
            ProcessLog::add("Ошибка API для круиза {$cruise['gama_cruise_id']}: " . $e->getMessage());
            // Если API недоступен, пробуем файловый способ
            $this->processCruisePricesFromFile($cruise);
        }
    }

    /**
     * Обработка цен каюты из API
     */
    private function processCabinPricesFromApi($cabin, $cruiseId, $gamaShipId)
    {
        $cabinId = $cabin['@attributes']['id'];
        $places = $cabin['@attributes']['places'];
        $available = $cabin['@attributes']['available'] ?? 0;
        
        if (!$available) {
            return 0; // Каюта недоступна
        }
        
        // Получаем информацию о категории каюты
        $categoryInfo = $this->getCabinCategoryInfo($cabinId, $gamaShipId);
        
        if (!$categoryInfo) {
            ProcessLog::add("Не найдена категория для каюты $cabinId на теплоходе $gamaShipId");
            return 0;
        }
        
        if (!isset($cabin['Cost'])) {
            return 0;
        }
        
        $costs = $cabin['Cost'];
        if (isset($costs['@attributes'])) {
            $costs = [$costs];
        }
        
        $pricesAdded = 0;
        foreach ($costs as $cost) {
            $costAttr = $cost['@attributes'];
            $persons = (int)($costAttr['persons'] ?? 0);
            $price1 = (int)($costAttr['std_3'] ?? 0);
            $price2 = (int)($costAttr['child_3'] ?? 0);
            
            if ($price1 > 0 && $persons == $places) {
                $this->db->savePrice($cruiseId, $categoryInfo['category_id'], $price1, $price2, $persons);
                $pricesAdded++;
            }
        }
        
        return $pricesAdded;
    }

    /**
     * Обработка цен из файла (резервный способ)
     */
    private function processCruisePricesFromFile($cruise)
    {
        // Получаем ID навигации для круиза
        $navigationId = $this->getNavigationIdForCruise($cruise['gama_cruise_id']);
        
        if (!$navigationId) {
            return;
        }
        
        $pricesData = $this->apiClient->getNavigationAvailableData($navigationId);
        
        if (!$pricesData || !isset($pricesData['Navigation']['RouteList']['Route'])) {
            return;
        }
        
        $routes = $pricesData['Navigation']['RouteList']['Route'];
        if (isset($routes['@attributes'])) {
            $routes = [$routes];
        }
        
        foreach ($routes as $route) {
            if ($route['@attributes']['id'] != $cruise['gama_cruise_id']) {
                continue;
            }
            
            if (!isset($route['CabinList']['Cabin'])) {
                continue;
            }
            
            $cabins = $route['CabinList']['Cabin'];
            if (isset($cabins['@attributes'])) {
                $cabins = [$cabins];
            }
            
            foreach ($cabins as $cabin) {
                $this->processCabinPricesFromFile($cabin, $cruise['id'], $cruise['gama_ship_id']);
            }
        }
    }

    /**
     * Обработка цен каюты из файла
     */
    private function processCabinPricesFromFile($cabin, $cruiseId, $gamaShipId)
    {
        $cabinId = $cabin['@attributes']['id'];
        $categoryId = $cabin['@attributes']['category_id'];
        
        // Получаем информацию о категории
        $categoryInfo = $this->getCabinCategoryInfo($cabinId, $gamaShipId);
        
        if (!$categoryInfo) {
            return;
        }
        
        if (!isset($cabin['Cost'])) {
            return;
        }
        
        $costs = $cabin['Cost'];
        if (isset($costs['@attributes'])) {
            $costs = [$costs];
        }
        
        foreach ($costs as $cost) {
            $costAttr = $cost['@attributes'];
            $persons = (int)($costAttr['persons'] ?? 0);
            $price1 = (int)($costAttr['std_3'] ?? 0);
            $price2 = (int)($costAttr['child_3'] ?? 0);
            
            if ($price1 > 0 && $persons == $categoryInfo['places']) {
                $this->db->savePrice($cruiseId, $categoryId, $price1, $price2, $persons);
            }
        }
    }

    /**
     * Получение ID навигации для круиза
     */
    private function getNavigationIdForCruise($cruiseId)
    {
        // Простая реализация - в реальности нужно искать в навигационных данных
        return 1; // Заглушка
    }

    /**
     * Получение информации о категории каюты
     */
    private function getCabinCategoryInfo($cabinId, $gamaShipId)
    {
        $shipsData = $this->apiClient->getGenericData();
        
        if (!isset($shipsData['ShipList']['Ship'])) {
            return null;
        }
        
        $ships = $shipsData['ShipList']['Ship'];
        if (isset($ships['@attributes'])) {
            $ships = [$ships];
        }
        
        foreach ($ships as $ship) {
            if ($ship['@attributes']['id'] != $gamaShipId) {
                continue;
            }
            
            if (!isset($ship['DeckList']['Deck'])) {
                continue;
            }
            
            $decks = $ship['DeckList']['Deck'];
            if (isset($decks['@attributes'])) {
                $decks = [$decks];
            }
            
            foreach ($decks as $deck) {
                if (!isset($deck['CabinList']['Cabin'])) {
                    continue;
                }
                
                $cabins = $deck['CabinList']['Cabin'];
                if (isset($cabins['@attributes'])) {
                    $cabins = [$cabins];
                }
                
                foreach ($cabins as $cabin) {
                    if ($cabin['@attributes']['id'] == $cabinId) {
                        return [
                            'category_id' => $cabin['@attributes']['category_id'],
                            'places' => $cabin['@attributes']['places']
                        ];
                    }
                }
            }
        }
        
        return null;
    }

    /**
     * Сохранение путевого листа
     */
    private function saveWaybill($cruiseId, $waybill)
    {
        foreach ($waybill as $index => $point) {
            $this->db->saveWaybill(
                $cruiseId,
                $point['town_name'] ?? '',
                $point['town'] ?? 0,
                $point['arrival_time'] ?? null,
                $point['departure_time'] ?? null,
                $point['bold'] ?? 0,
                $index
            );
        }
    }

    /**
     * Получение маршрута Gama
     */
    private function getGammaWaybill($pathList, $pathSId, $pathFId, $shortPathIds)
    {
        $shortPathIds = array_unique($shortPathIds);
        $items = [];
        $points = 0;
        
        $paths = $pathList;
        if (isset($paths['@attributes'])) {
            $paths = [$paths];
        }
        
        foreach ($paths as $item) {
            $pathId = (int)$item['@attributes']['id'];
            if ($pathId >= $pathSId && $pathId <= $pathFId) {
                if ($pathId == $pathSId || $pathId == $pathFId) {
                    $points++;
                }
                
                $gamaTownName = $item['@attributes']['town_name'];
                $townId = $this->getter->getTownId($gamaTownName, 'gama');
                
                $items[] = [
                    'town' => $townId,
                    'town_name' => $gamaTownName,
                    'excursion' => '',
                    'bold' => in_array($townId, $shortPathIds),
                    'arrival_time' => $item['@attributes']['s'] ?? null,
                    'departure_time' => $item['@attributes']['f'] ?? null
                ];
            }
            if ($points == 2) {
                break;
            }
        }
        
        return $items;
    }

    /**
     * Создание расписания Gama V2
     */
    private function gamaDesignScheduleV2($pathList, $pathSId, $pathFId)
    {
        $gamaCruiseRoute = [];
        
        $paths = $pathList;
        if (isset($paths['@attributes'])) {
            $paths = [$paths];
        }
        
        foreach ($paths as $path) {
            $attrs = $path['@attributes'] ?? [];
            $pathId = (int)($attrs['id'] ?? 0);
            
            if ($pathId < $pathSId || $pathId > $pathFId) {
                continue;
            }
            
            $townName = $attrs['town_name'] ?? '';
            $startTime = $attrs['s'] ?? null;
            $endTime = $attrs['f'] ?? null;
            
            if (!$townName || !$startTime || !$endTime) {
                continue;
            }
            
            $gamaCruiseRoute[] = [
                'town' => $townName,
                'start' => date('d.m.Y H:i:s', strtotime($startTime)),
                'end' => date('d.m.Y H:i:s', strtotime($endTime)),
            ];
        }
        
        $tableData = [];
        
        foreach ($gamaCruiseRoute as $item) {
            $town = $item['town'];
            $fullDate1 = Carbon::parse($item['start']);
            $fullDate2 = Carbon::parse($item['end']);
            $diffInDays = $fullDate2->diffInDays($fullDate1);
            $stay = $fullDate2->diffInSeconds($fullDate1);
            $stay = gmdate('H:i', $stay);
            
            if ($diffInDays === 0) {
                $tableData[] = [
                    'date' => $fullDate1->format('d.m.Y'),
                    'town' => $town,
                    'arrival' => $fullDate1->format('H:i'),
                    'stay' => $stay,
                    'departure' => $fullDate2->format('H:i'),
                ];
            } else {
                $tableData[] = [
                    'date' => $fullDate1->format('d.m.Y'),
                    'town' => $town,
                    'arrival' => $fullDate1->format('H:i'),
                    'stay' => $stay,
                    'departure' => '',
                ];
                
                $tableData[] = [
                    'date' => $fullDate2->format('d.m.Y'),
                    'town' => $town,
                    'arrival' => '',
                    'stay' => '',
                    'departure' => $fullDate2->format('H:i'),
                ];
            }
        }
        
        return View::make('mcmraak.rivercrs::gama_schedule', ['table' => $tableData])->render();
    }

    /**
     * Получение коротких ID путевых листов
     */
    private function getShortWaybillIds($string)
    {
        // Простая реализация для получения коротких ID
        // В оригинале это более сложная логика
        return [];
    }
}