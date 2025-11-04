<?php namespace Zen\Worker\Console\infoflot;

use Carbon\Carbon;
use Exception;
use Zen\Worker\Classes\ProcessLog;

class InfoflotDataProcessor
{
    private $db;
    private $apiClient;
    private $timeout;
    private $limit;

    public function __construct($database, $apiKey, $timeout = 30, $limit = null)
    {
        // Убираем ограничение времени выполнения
        set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('max_input_time', -1);
        
        $this->db = $database;
        $this->apiClient = new InfoflotApiClient($apiKey, $timeout);
        $this->timeout = $timeout;
        $this->limit = $limit;
    }

    /**
     * Обработка данных о теплоходах
     */
    public function processShipsData()
    {
        ProcessLog::add('Начинаем обработку судов Infoflot...');
        
        $page = 1;
        $limit = 100;
        $ships = [];
        
        // Получаем первую страницу для определения количества страниц
        $firstPage = $this->apiClient->getShips($page, $limit);
        $totalPages = $firstPage['pagination']['pages']['total'] ?? 1;
        
        // Обрабатываем первую страницу
        if (isset($firstPage['data'])) {
            foreach ($firstPage['data'] as $ship) {
                $ships[] = [
                    'id' => (int)$ship['id'],
                    'name' => $ship['name'],
                    'type' => $ship['typeName'] ?? null,
                    'operator_name' => $ship['operatorName'] ?? null,
                    'description' => $ship['description'] ?? ''
                ];
            }
        }
        
        // Обрабатываем остальные страницы
        for ($page = 2; $page <= $totalPages; $page++) {
            ProcessLog::add("Обработка страницы судов: $page из $totalPages");
            
            $response = $this->apiClient->getShips($page, $limit);
            
            if (isset($response['data'])) {
                foreach ($response['data'] as $ship) {
                    $ships[] = [
                        'id' => (int)$ship['id'],
                        'name' => $ship['name'],
                        'type' => $ship['typeName'] ?? null,
                        'operator_name' => $ship['operatorName'] ?? null,
                        'description' => $ship['description'] ?? ''
                    ];
                }
            }
            
            // Ограничение для тестирования
            if ($this->limit && count($ships) >= $this->limit) {
                $ships = array_slice($ships, 0, $this->limit);
                break;
            }
        }
        
        // Сохраняем суда
        if (!empty($ships)) {
            $this->db->saveShipsBatch($ships);
            ProcessLog::add("Сохранено судов: " . count($ships));
        }
        
        return $ships;
    }

    /**
     * Обработка круизов и цен
     */
    public function processCruisesData()
    {
        ProcessLog::add('Начинаем обработку круизов Infoflot...');
        
        $ships = $this->db->getAllShips();
        $now = Carbon::now();
        $processedCruises = 0;
        $processedPrices = 0;
        
        foreach ($ships as $ship) {
            $shipId = $ship['id'];
            $shipName = $ship['name'];
            
            ProcessLog::add("Обработка круизов для судна: $shipName (ID: $shipId)");
            
            $page = 1;
            $limit = 500;
            $date = date('Y-m-d');
            
            while (true) {
                try {
                    $cruisesResponse = $this->apiClient->getCruisesByShip($shipId, $page, $limit, $date);
                    
                    if (!$cruisesResponse || !isset($cruisesResponse['data'])) {
                        break;
                    }
                    
                    $cruises = $cruisesResponse['data'];
                    $totalPages = $cruisesResponse['pagination']['pages']['total'] ?? 1;
                    
                    foreach ($cruises as $cruise) {
                        // Пропускаем прошедшие круизы
                        $cruiseDate = Carbon::parse($cruise['dateStart']);
                        if ($cruiseDate < $now) {
                            continue;
                        }
                        
                        // Получаем цены для круиза
                        $pricesData = $this->apiClient->getCruiseCabins($cruise['id']);
                        
                        if (!$pricesData || empty($pricesData['prices']) || empty($pricesData['cabins'])) {
                            continue; // Пропускаем круизы без цен
                        }
                        
                        // Сохраняем круиз
                        $cruiseData = [
                            'infoflot_cruise_id' => (int)$cruise['id'],
                            'infoflot_ship_id' => $shipId,
                            'name' => $cruise['name'],
                            'beautiful_name' => $cruise['beautifulName'] ?? null,
                            'route' => $cruise['route'] ?? '',
                            'route_short' => $cruise['routeShort'] ?? null,
                            'date_start' => $cruise['dateStart'],
                            'date_end' => $cruise['dateEnd'],
                            'date_start_timestamp' => $cruise['dateStartTimestamp'] ?? null,
                            'date_end_timestamp' => $cruise['dateEndTimestamp'] ?? null,
                            'days' => $cruise['days'] ?? null,
                            'nights' => $cruise['nights'] ?? null,
                            'description' => $cruise['description'] ?? null
                        ];
                        
                        $this->db->saveCruise($cruiseData);
                        $processedCruises++;
                        
                        // Сохраняем палубы и каюты
                        $this->processShipDecksAndCabins($shipId, $pricesData);
                        
                        // Сохраняем цены
                        $prices = $this->processPrices($cruise['id'], $pricesData);
                        $processedPrices += count($prices);
                        
                        if ($prices) {
                            $this->db->savePricesBatch($prices);
                        }
                        
                        ProcessLog::add("Обработан круиз: {$cruise['name']} (ID: {$cruise['id']})");
                    }
                    
                    // Проверяем, есть ли еще страницы
                    if ($page >= $totalPages) {
                        break;
                    }
                    
                    $page++;
                    
                } catch (Exception $e) {
                    if (strpos($e->getMessage(), 'Not found') !== false) {
                        break;
                    }
                    ProcessLog::add("Ошибка при обработке круизов для судна $shipId: " . $e->getMessage());
                    break;
                }
            }
        }
        
        ProcessLog::add("Обработано круизов: $processedCruises, цен: $processedPrices");
    }

    /**
     * Обработка палуб и кают судна
     */
    private function processShipDecksAndCabins($shipId, $pricesData)
    {
        if (!isset($pricesData['cabins'])) {
            return;
        }
        
        $decks = [];
        $cabins = [];
        $cabinCategories = [];
        
        foreach ($pricesData['cabins'] as $cabin) {
            // Сохраняем палубу
            if (isset($cabin['deck'])) {
                $deck = $cabin['deck'];
                $deckId = (int)$deck['id'];
                $deckName = $deck['name'];
                $deckPosition = $deck['position'] ?? null;
                
                if (!isset($decks[$deckId])) {
                    $this->db->saveDeck($deckId, $deckName, $shipId, $deckPosition);
                    $decks[$deckId] = true;
                }
            }
            
            // Сохраняем категорию кают
            if (isset($cabin['type_id'])) {
                $typeId = (int)$cabin['type_id'];
                $typeName = $cabin['type_name'] ?? $cabin['typeName'] ?? '';
                $placesMain = $cabin['places']['main'] ?? 1;
                
                if (!isset($cabinCategories[$typeId])) {
                    $deckId = isset($cabin['deck']) ? (int)$cabin['deck']['id'] : null;
                    $this->db->saveCabinCategory($typeId, $typeName, $placesMain, $deckId, $shipId);
                    $cabinCategories[$typeId] = true;
                }
            }
            
            // Сохраняем каюту
            if (isset($cabin['id'])) {
                $cabinId = (int)$cabin['id'];
                $cabinName = $cabin['name'] ?? '';
                $typeId = isset($cabin['type_id']) ? (int)$cabin['type_id'] : null;
                $deckId = isset($cabin['deck']) ? (int)$cabin['deck']['id'] : null;
                $placesMain = $cabin['places']['main'] ?? 1;
                $placesAdditional = $cabin['places']['additional'] ?? 0;
                
                $this->db->saveCabin($cabinId, $shipId, $deckId, $typeId, $cabinName, $placesMain, $placesAdditional);
            }
        }
    }

    /**
     * Обработка цен для круиза
     */
    private function processPrices($cruiseId, $pricesData)
    {
        $prices = [];
        
        if (!isset($pricesData['prices']) || !isset($pricesData['cabins'])) {
            return $prices;
        }
        
        // Создаем маппинг type_id -> cabin_category_id
        $typeToCategoryMap = [];
        foreach ($pricesData['cabins'] as $cabin) {
            if (isset($cabin['type_id'])) {
                $typeId = (int)$cabin['type_id'];
                $typeToCategoryMap[$typeId] = $typeId; // В Infoflot type_id = cabin_category_id
            }
        }
        
        // Обрабатываем цены
        foreach ($pricesData['prices'] as $typeId => $priceData) {
            $typeIdInt = (int)$typeId;
            
            if (!isset($typeToCategoryMap[$typeIdInt])) {
                continue;
            }
            
            $cabinCategoryId = $typeToCategoryMap[$typeIdInt];
            $typeName = $priceData['type_name'] ?? '';
            
            // Получаем цену взрослого (main_bottom.adult)
            $priceAdult = null;
            if (isset($priceData['prices']['main_bottom']['adult'])) {
                $priceAdult = (int)$priceData['prices']['main_bottom']['adult'];
            }
            
            // Получаем цену по умолчанию
            $priceDefault = null;
            if (isset($priceData['prices']['default'])) {
                $priceDefault = (int)$priceData['prices']['default'];
            }
            
            if ($priceAdult !== null) {
                $prices[] = [
                    'cruise_id' => (int)$cruiseId,
                    'cabin_category_id' => $cabinCategoryId,
                    'type_id' => $typeIdInt,
                    'type_name' => $typeName,
                    'price_adult' => $priceAdult,
                    'price_default' => $priceDefault
                ];
            }
        }
        
        return $prices;
    }

}

