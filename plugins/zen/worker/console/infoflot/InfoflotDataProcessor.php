<?php namespace Zen\Worker\Console\infoflot;

use Carbon\Carbon;
use Exception;
use PDO;
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
        $totalShips = count($ships);
        $currentShipIndex = 0;
        
        foreach ($ships as $ship) {
            $currentShipIndex++;
            // Пропускаем морские суда (они не имеют речных круизов)
            // Фильтруем по названиям, которые содержат морские круизы
            $shipName = $ship['name'] ?? '';
            $shipType = $ship['type'] ?? '';
            
            // Морские суда обычно имеют тип "Круизный лайнер" или названия типа "MSC", "Celebrity", "Royal Caribbean"
            if (stripos($shipType, 'лайнер') !== false || 
                stripos($shipName, 'MSC') !== false ||
                stripos($shipName, 'Celebrity') !== false ||
                stripos($shipName, 'Royal Caribbean') !== false ||
                stripos($shipName, 'Allure') !== false ||
                stripos($shipName, 'Anthem') !== false ||
                stripos($shipName, 'Freedom') !== false ||
                stripos($shipName, 'Harmony') !== false ||
                stripos($shipName, 'Independence') !== false ||
                stripos($shipName, 'Jewel') !== false ||
                stripos($shipName, 'Liberty') !== false ||
                stripos($shipName, 'Brilliance') !== false ||
                stripos($shipName, 'Costa') !== false) {
                ProcessLog::add("Пропуск морского судна: $shipName (ID: {$ship['id']})");
                continue;
            }
            
            $shipId = $ship['id'];
            
            ProcessLog::add("Обработка круизов для судна: $shipName (ID: $shipId) [$currentShipIndex/$totalShips]");
            
            $page = 1;
            $limit = 500;
            $date = date('Y-m-d');
            
            while (true) {
                try {
                    $cruisesResponse = $this->apiClient->getCruisesByShip($shipId, $page, $limit, $date);
                    
                    if (!$cruisesResponse) {
                        ProcessLog::add("API вернул null для судна $shipId, страница $page - нет круизов");
                        break;
                    }
                    
                    if (!isset($cruisesResponse['data'])) {
                        ProcessLog::add("API не вернул data для судна $shipId, страница $page");
                        break;
                    }
                    
                    $cruises = $cruisesResponse['data'];
                    
                    // Проверяем, что это массив
                    if (!is_array($cruises)) {
                        ProcessLog::add("Data не является массивом для судна $shipId, страница $page");
                        break;
                    }
                    
                    if (empty($cruises)) {
                        ProcessLog::add("Пустой массив круизов для судна $shipId, страница $page");
                        break;
                    }
                    
                    $cruisesCount = count($cruises);
                    ProcessLog::add("Найдено круизов для судна $shipId, страница $page: $cruisesCount");
                    
                    $totalPages = $cruisesResponse['pagination']['pages']['total'] ?? 1;
                    $skippedPast = 0;
                    $skippedNoPrices = 0;
                    $cruiseIndex = 0;
                    
                    foreach ($cruises as $index => $cruise) {
                        $cruiseIndex++;
                        // Показываем прогресс каждые 10 круизов
                        if ($cruiseIndex % 10 == 0) {
                            ProcessLog::add("Обработка круиза $cruiseIndex/$cruisesCount для судна $shipId...");
                        }
                        // Проверяем, что круиз это массив и имеет нужные поля
                        if (!is_array($cruise)) {
                            ProcessLog::add("Пропуск элемента $index - не является массивом, тип: " . gettype($cruise));
                            continue;
                        }
                        
                        if (!isset($cruise['id'])) {
                            ProcessLog::add("Пропуск элемента $index - отсутствует поле 'id'. Ключи: " . implode(', ', array_keys($cruise)));
                            continue;
                        }
                        
                        if (!isset($cruise['dateStart'])) {
                            ProcessLog::add("Пропуск круиза {$cruise['id']} - отсутствует поле 'dateStart'");
                            continue;
                        }
                        
                        // Пропускаем прошедшие круизы
                        try {
                            // API может возвращать дату в формате "2026-04-26" без времени
                            $dateStartStr = $cruise['dateStart'];
                            if (strlen($dateStartStr) == 10 && substr_count($dateStartStr, '-') == 2) {
                                // Добавляем время если его нет
                                $dateStartStr .= ' 00:00:00';
                            }
                            $cruiseDate = Carbon::parse($dateStartStr);
                            if ($cruiseDate < $now) {
                                $skippedPast++;
                                continue;
                            }
                        } catch (\Exception $e) {
                            ProcessLog::add("Ошибка парсинга даты круиза {$cruise['id']}: " . $e->getMessage() . ". Дата: " . ($cruise['dateStart'] ?? 'null'));
                            continue;
                        }
                        
                        // Получаем цены для круиза
                        $cruiseId = $cruise['id'];
                        // Убираем слишком детальное логирование для скорости
                        // ProcessLog::add("Получение цен для круиза ID: $cruiseId");
                        $pricesData = $this->apiClient->getCruiseCabins($cruiseId);
                        
                        if (!$pricesData) {
                            $skippedNoPrices++;
                            ProcessLog::add("Круиз {$cruise['id']} пропущен - нет данных о ценах (API вернул null)");
                            continue;
                        }
                        
                        if (empty($pricesData['prices']) || empty($pricesData['cabins'])) {
                            $skippedNoPrices++;
                            ProcessLog::add("Круиз {$cruise['id']} пропущен - пустые цены или каюты");
                            continue; // Пропускаем круизы без цен
                        }
                        
                        // Сохраняем круиз
                        $cruiseData = [
                            'infoflot_cruise_id' => (int)$cruiseId,
                            'infoflot_ship_id' => $shipId,
                            'name' => $cruise['name'] ?? '',
                            'beautiful_name' => $cruise['beautifulName'] ?? null,
                            'route' => $cruise['route'] ?? '',
                            'route_short' => $cruise['routeShort'] ?? null,
                            'date_start' => $cruise['dateStart'],
                            'date_end' => $cruise['dateEnd'] ?? null,
                            'date_start_timestamp' => $cruise['dateStartTimestamp'] ?? null,
                            'date_end_timestamp' => $cruise['dateEndTimestamp'] ?? null,
                            'days' => $cruise['days'] ?? null,
                            'nights' => $cruise['nights'] ?? null,
                            'description' => $cruise['description'] ?? null
                        ];
                        
                        try {
                            $this->db->saveCruise($cruiseData);
                            $processedCruises++;
                            // Логируем только каждые 50 круизов для скорости
                            if ($processedCruises % 50 == 0) {
                                ProcessLog::add("Сохранено круизов: $processedCruises, цен: $processedPrices");
                            }
                        } catch (\Exception $e) {
                            ProcessLog::add("Ошибка сохранения круиза $cruiseId: " . $e->getMessage());
                            continue;
                        }
                        
                        // Сохраняем палубы и каюты (не критично, если ошибка)
                        try {
                            $this->processShipDecksAndCabins($shipId, $pricesData);
                        } catch (\Exception $e) {
                            // Логируем ошибки для отладки
                            ProcessLog::add("Ошибка сохранения палуб/кают для круиза $cruiseId: " . $e->getMessage());
                        } catch (\Error $e) {
                            ProcessLog::add("PHP ошибка при сохранении палуб/кают для круиза $cruiseId: " . $e->getMessage());
                        }
                        
                        // Сохраняем цены
                        $prices = $this->processPrices($cruiseId, $pricesData);
                        $processedPrices += count($prices);
                        
                        if ($prices && !empty($prices)) {
                            try {
                                $this->db->savePricesBatch($prices);
                                // Убираем детальное логирование для скорости
                            } catch (\Exception $e) {
                                ProcessLog::add("Ошибка сохранения цен для круиза $cruiseId: " . $e->getMessage());
                            }
                        }
                    }
                    
                    if ($skippedPast > 0) {
                        ProcessLog::add("Пропущено прошедших круизов: $skippedPast");
                    }
                    if ($skippedNoPrices > 0) {
                        ProcessLog::add("Пропущено круизов без цен: $skippedNoPrices");
                    }
                    
                    // Проверяем, есть ли еще страницы
                    if ($page >= $totalPages) {
                        break;
                    }
                    
                    $page++;
                    
                } catch (Exception $e) {
                    // Если "Not found" или "Resource not found" - это нормально, значит нет круизов для этого судна
                    if (strpos($e->getMessage(), 'Not found') !== false || 
                        strpos($e->getMessage(), 'Resource not found') !== false) {
                        break;
                    }
                    // Для других ошибок логируем и продолжаем
                    ProcessLog::add("Ошибка при обработке круизов для судна $shipId: " . $e->getMessage());
                    break;
                } catch (\Error $e) {
                    // Ловим PHP ошибки типа "Illegal string offset"
                    ProcessLog::add("Ошибка типа данных при обработке круизов для судна $shipId: " . $e->getMessage());
                    break;
                }
            }
        }
        
        ProcessLog::add("=== ИТОГИ ===");
        ProcessLog::add("Обработано судов: $totalShips");
        ProcessLog::add("Обработано круизов: $processedCruises");
        ProcessLog::add("Обработано цен: $processedPrices");
    }

    /**
     * Обработка палуб и кают судна
     */
    private function processShipDecksAndCabins($shipId, $pricesData)
    {
        // Проверяем наличие данных о каютах
        if (!isset($pricesData['cabins'])) {
            ProcessLog::add("Нет данных о каютах для судна $shipId");
            return;
        }
        
        // В API Infoflot cabins может быть объектом (ассоциативный массив) или массивом
        $cabinsData = $pricesData['cabins'];
        if (!is_array($cabinsData)) {
            ProcessLog::add("Данные кают для судна $shipId не являются массивом");
            return;
        }
        
        $cabinsCount = count($cabinsData);
        ProcessLog::add("Обработка палуб и кают для судна $shipId: найдено $cabinsCount кают");
        
        $decks = [];
        $cabins = [];
        $cabinCategories = [];
        $savedDecksCount = 0;
        
        // Обрабатываем каюты (может быть как массив, так и объект)
        foreach ($cabinsData as $cabinKey => $cabin) {
            // Проверяем, что это массив
            if (!is_array($cabin)) {
                continue;
            }
            
            // Сохраняем палубу
            // В API Infoflot структура: deck (название), deck_id (ID)
            if (isset($cabin['deck_id']) || isset($cabin['deck'])) {
                $deckId = null;
                $deckName = null;
                $deckPosition = null;
                
                // Получаем ID палубы
                if (isset($cabin['deck_id'])) {
                    $deckId = (int)$cabin['deck_id'];
                }
                
                // Получаем название палубы
                if (isset($cabin['deck'])) {
                    if (is_string($cabin['deck'])) {
                        // deck это строка с названием палубы
                        $deckName = $cabin['deck'];
                    } elseif (is_array($cabin['deck']) && isset($cabin['deck']['name'])) {
                        // Альтернативный формат: deck = {name, id, ...}
                        $deckName = $cabin['deck']['name'];
                        if (isset($cabin['deck']['id']) && !$deckId) {
                            $deckId = (int)$cabin['deck']['id'];
                        }
                        if (isset($cabin['deck']['position'])) {
                            $deckPosition = $cabin['deck']['position'];
                        }
                    }
                }
                
                // Если ID не найден, но есть название, создаём ID на основе названия
                if (!$deckId && !empty($deckName)) {
                    // Генерируем ID на основе хеша названия (для уникальности)
                    $deckId = abs(crc32($deckName . $shipId)) % 100000;
                }
                
                if ($deckId && !isset($decks[$deckId])) {
                    try {
                        // Если название не найдено, пытаемся получить из других источников
                        if (empty($deckName)) {
                            // Проверяем, есть ли уже палуба в базе
                            $stmt = $this->db->getPdo()->prepare("SELECT name FROM decks WHERE id = ?");
                            $stmt->execute([$deckId]);
                            $existing = $stmt->fetch(\PDO::FETCH_ASSOC);
                            if ($existing && !empty($existing['name'])) {
                                $deckName = $existing['name'];
                            } else {
                                $deckName = 'Палуба ' . $deckId;
                            }
                        }
                        
                        $this->db->saveDeck($deckId, $deckName, $shipId, $deckPosition);
                        $decks[$deckId] = true;
                        $savedDecksCount++;
                    } catch (\Exception $e) {
                        ProcessLog::add("Ошибка сохранения палубы $deckId: " . $e->getMessage());
                    }
                }
            }
            
            // Сохраняем категорию кают
            if (isset($cabin['type_id'])) {
                $typeId = (int)$cabin['type_id'];
                // Получаем название категории из разных источников
                $typeName = '';
                if (!empty($cabin['type_name'])) {
                    $typeName = $cabin['type_name'];
                } elseif (!empty($cabin['typeName'])) {
                    $typeName = $cabin['typeName'];
                }
                
                $placesMain = 1;
                if (isset($cabin['places']) && is_array($cabin['places'])) {
                    $placesMain = $cabin['places']['main'] ?? 1;
                }
                
                if (!isset($cabinCategories[$typeId])) {
                    $deckId = null;
                    if (isset($cabin['deck']) && is_array($cabin['deck']) && isset($cabin['deck']['id'])) {
                        $deckId = (int)$cabin['deck']['id'];
                    }
                    try {
                        // Если название пустое, попробуем получить из цен позже
                        $this->db->saveCabinCategory($typeId, $typeName, $placesMain, $deckId, $shipId);
                        $cabinCategories[$typeId] = true;
                    } catch (\Exception $e) {
                        // Игнорируем ошибки сохранения категории (возможно уже существует)
                    }
                } elseif (!empty($typeName)) {
                    // Обновляем название, если оно было пустым
                    try {
                        $stmt = $this->db->getPdo()->prepare("SELECT name FROM cabin_categories WHERE id = ?");
                        $stmt->execute([$typeId]);
                        $current = $stmt->fetch(\PDO::FETCH_ASSOC);
                        if (!$current || empty($current['name'])) {
                            $updateStmt = $this->db->getPdo()->prepare("UPDATE cabin_categories SET name = ? WHERE id = ?");
                            $updateStmt->execute([$typeName, $typeId]);
                        }
                    } catch (\Exception $e) {
                        // Игнорируем ошибки
                    }
                }
            }
            
            // Сохраняем каюту
            if (isset($cabin['id'])) {
                $cabinId = (int)$cabin['id'];
                $cabinName = $cabin['name'] ?? '';
                $typeId = isset($cabin['type_id']) ? (int)$cabin['type_id'] : null;
                $deckId = null;
                // Получаем deck_id из разных источников
                if (isset($cabin['deck_id'])) {
                    $deckId = (int)$cabin['deck_id'];
                } elseif (isset($cabin['deck']) && is_array($cabin['deck']) && isset($cabin['deck']['id'])) {
                    $deckId = (int)$cabin['deck']['id'];
                }
                $placesMain = 1;
                $placesAdditional = 0;
                if (isset($cabin['places']) && is_array($cabin['places'])) {
                    $placesMain = $cabin['places']['main'] ?? 1;
                    $placesAdditional = $cabin['places']['additional'] ?? 0;
                }
                
                try {
                    $this->db->saveCabin($cabinId, $shipId, $deckId, $typeId, $cabinName, $placesMain, $placesAdditional);
                } catch (\Exception $e) {
                    // Игнорируем ошибки сохранения каюты (возможно уже существует)
                }
            }
        }
        
        if ($savedDecksCount > 0) {
            ProcessLog::add("Сохранено палуб для судна $shipId: $savedDecksCount");
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
        
        // Создаем маппинг type_id -> cabin_category_id и type_id -> название
        $typeToCategoryMap = [];
        $typeToNameMap = [];
        foreach ($pricesData['cabins'] as $cabin) {
            if (isset($cabin['type_id'])) {
                $typeId = (int)$cabin['type_id'];
                $typeToCategoryMap[$typeId] = $typeId; // В Infoflot type_id = cabin_category_id
                // Сохраняем название из каюты
                if (isset($cabin['type_name']) && !empty($cabin['type_name'])) {
                    $typeToNameMap[$typeId] = $cabin['type_name'];
                } elseif (isset($cabin['typeName']) && !empty($cabin['typeName'])) {
                    $typeToNameMap[$typeId] = $cabin['typeName'];
                }
            }
        }
        
        // Обрабатываем цены
        foreach ($pricesData['prices'] as $typeId => $priceData) {
            $typeIdInt = (int)$typeId;
            
            if (!isset($typeToCategoryMap[$typeIdInt])) {
                continue;
            }
            
            $cabinCategoryId = $typeToCategoryMap[$typeIdInt];
            
            // Получаем название категории: сначала из priceData, потом из кают
            $typeName = $priceData['type_name'] ?? '';
            if (empty($typeName) && isset($typeToNameMap[$typeIdInt])) {
                $typeName = $typeToNameMap[$typeIdInt];
            }
            
            // ВАЖНО: Обновляем название категории в базе, если оно пустое или не соответствует
            if (!empty($typeName)) {
                try {
                    // Получаем текущую категорию
                    $stmt = $this->db->getPdo()->prepare("SELECT name FROM cabin_categories WHERE id = ?");
                    $stmt->execute([$cabinCategoryId]);
                    $currentCategory = $stmt->fetch(\PDO::FETCH_ASSOC);
                    
                    // Обновляем название, если оно пустое или отличается
                    if (!$currentCategory || empty($currentCategory['name']) || $currentCategory['name'] !== $typeName) {
                        $updateStmt = $this->db->getPdo()->prepare("UPDATE cabin_categories SET name = ? WHERE id = ?");
                        $updateStmt->execute([$typeName, $cabinCategoryId]);
                    }
                } catch (\Exception $e) {
                    // Игнорируем ошибки обновления
                }
            }
            
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

