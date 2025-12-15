<?php namespace Zen\Worker\Console\gama;

use PDO;
use Exception;
use Zen\Worker\Console\unified\UnifiedDatabase;
use Zen\Worker\Console\transfer\TransferConfig;

/**
 * GamaDatabase - наследуется от UnifiedDatabase
 * Использует единую структуру SQLite для всех источников
 */
class GamaDatabase extends UnifiedDatabase
{
    /**
     * Конструктор - передает путь к базе данных в родительский класс
     */
    public function __construct(bool $createIfMissing = false)
    {
        // Получаем путь к базе данных из конфигурации
        $dbPath = TransferConfig::getDbPath('gama');
        if (!file_exists($dbPath) && !$createIfMissing) {
            throw new Exception("База данных gama_data.sqlite не найдена: {$dbPath}");
        }

        if ($createIfMissing) {
            $dir = dirname($dbPath);
            if (!is_dir($dir)) {
                if (!@mkdir($dir, 0775, true)) {
                    throw new Exception("Не удалось создать директорию для SQLite: {$dir}");
                }
            }
            if (!is_writable($dir)) {
                throw new Exception("Директория SQLite недоступна для записи: {$dir}");
            }
        }

        parent::__construct($dbPath);
        
        // Миграция: добавляем поля для обратной совместимости (если их нет)
        $this->migrateLegacyFields();
    }
    
    /**
     * Миграция для обратной совместимости со старыми данными
     * Добавляет поля, которые могут использоваться в старых версиях
     */
    private function migrateLegacyFields()
    {
        // Миграция структуры таблиц для единой схемы
        $this->migrateTableStructure();
    }
    
    /**
     * Миграция структуры таблиц для единой схемы
     * Добавляет недостающие поля из UnifiedDatabase
     */
    private function migrateTableStructure()
    {
        // Миграция таблицы ships
        $this->addColumnIfNotExists('ships', 'description', 'TEXT');
        $this->addColumnIfNotExists('ships', 'type', 'TEXT');
        $this->addColumnIfNotExists('ships', 'operator_name', 'TEXT');
        $this->addColumnIfNotExists('ships', 'extra_data', 'TEXT');
        
        // Миграция таблицы decks
        $this->addColumnIfNotExists('decks', 'ship_id', 'INTEGER');
        $this->addColumnIfNotExists('decks', 'position', 'INTEGER');
        $this->addColumnIfNotExists('decks', 'meta_id', 'INTEGER');
        $this->addColumnIfNotExists('decks', 'meta_name', 'TEXT');
        $this->addColumnIfNotExists('decks', 'extra_data', 'TEXT');
        
        // Миграция таблицы cabin_categories
        $this->addColumnIfNotExists('cabin_categories', 'description', 'TEXT');
        $this->addColumnIfNotExists('cabin_categories', 'places', 'INTEGER DEFAULT 1');
        $this->addColumnIfNotExists('cabin_categories', 'places_extra', 'INTEGER');
        $this->addColumnIfNotExists('cabin_categories', 'meta_id', 'INTEGER');
        $this->addColumnIfNotExists('cabin_categories', 'meta_name', 'TEXT');
        $this->addColumnIfNotExists('cabin_categories', 'extra_data', 'TEXT');
        
        // Миграция таблицы cruises
        $this->addColumnIfNotExists('cruises', 'days', 'INTEGER');
        $this->addColumnIfNotExists('cruises', 'nights', 'INTEGER');
        $this->addColumnIfNotExists('cruises', 'description', 'TEXT');
        $this->addColumnIfNotExists('cruises', 'schedule_html', 'TEXT');
        $this->addColumnIfNotExists('cruises', 'extra_data', 'TEXT');
        
        // Миграция таблицы prices
        $this->addColumnIfNotExists('prices', 'price_extra', 'INTEGER');
        $this->addColumnIfNotExists('prices', 'places_qnt', 'INTEGER DEFAULT 1');
        $this->addColumnIfNotExists('prices', 'deck_id', 'INTEGER');
        $this->addColumnIfNotExists('prices', 'tariff_name', 'TEXT');
        $this->addColumnIfNotExists('prices', 'extra_data', 'TEXT');
    }
    
    /**
     * Добавляет колонку в таблицу, если её нет
     */
    private function addColumnIfNotExists($tableName, $columnName, $columnDefinition)
    {
        try {
            // Проверяем, существует ли колонка
            $stmt = $this->getPdo()->prepare("PRAGMA table_info($tableName)");
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $columnExists = false;
            foreach ($columns as $column) {
                if ($column['name'] === $columnName) {
                    $columnExists = true;
                    break;
                }
            }
            
            if (!$columnExists) {
                $this->getPdo()->exec("ALTER TABLE $tableName ADD COLUMN $columnName $columnDefinition");
            }
        } catch (\Exception $e) {
            // Игнорируем ошибки миграции
        }
    }

    /**
     * Сохранение теплохода (id = gama_ship_id)
     * Адаптирован для единого интерфейса UnifiedDatabase
     */
    public function saveShip($gamaShipId, $name, $description = '')
    {
        return parent::saveShip($gamaShipId, $name, ['description' => $description]);
    }

    /**
     * Batch сохранение теплоходов
     */
    public function saveShipsBatch($ships)
    {
        // Конвертируем в единый формат и используем родительский метод
        $convertedShips = [];
        foreach ($ships as $ship) {
            $convertedShips[] = [
                'id' => $ship['id'],
                'name' => $ship['name'],
                'description' => $ship['description'] ?? null
            ];
        }
        parent::saveShipsBatch($convertedShips);
    }

    /**
     * Сохранение круиза (id = gama_cruise_id, ship_id = gama_ship_id)
     * Адаптирован для единого интерфейса UnifiedDatabase
     * Конвертирует path_s_id/path_f_id → extra_data
     * Конвертирует waybills из таблицы → JSON в waybill_data
     * Поддерживает старую сигнатуру для обратной совместимости
     */
    public function saveCruise($idOrData, $shipId = null, $name = null, $dateStart = null, $dateEnd = null, $data = [])
    {
        // Если первый параметр - массив (старый формат)
        if (is_array($idOrData)) {
            $data = $idOrData;
            
            // Подготавливаем extra_data с path_s_id и path_f_id
            $extraData = [];
            if (isset($data['path_s_id'])) {
                $extraData['path_s_id'] = $data['path_s_id'];
            }
            if (isset($data['path_f_id'])) {
                $extraData['path_f_id'] = $data['path_f_id'];
            }
            
            // Получаем waybill_data (может быть уже JSON или нужно получить из таблицы waybills)
            $waybillData = $data['waybill_data'] ?? '[]';
            
            // Если waybill_data это строка JSON, декодируем для проверки
            if (is_string($waybillData)) {
                $decoded = json_decode($waybillData, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $waybillData = $decoded;
                }
            }
            
            // Если waybill_data не массив, пытаемся получить из таблицы waybills
            if (!is_array($waybillData) && isset($data['gama_cruise_id'])) {
                $waybillData = $this->getCruiseWaybillAsArray($data['gama_cruise_id']);
            }
            
            // Вызываем родительский метод с единым интерфейсом
            return parent::saveCruise(
                $data['gama_cruise_id'],
                $data['gama_ship_id'],
                $data['name'],
                $data['date_start'],
                $data['date_end'],
                [
                    'route' => $data['route_name'] ?? null,
                    'waybill_data' => $waybillData,
                    'schedule_html' => $data['schedule_html'] ?? null,
                    'extra_data' => !empty($extraData) ? $extraData : null
                ]
            );
        }
        
        // Новый формат - вызываем родительский метод напрямую
        return parent::saveCruise($idOrData, $shipId, $name, $dateStart, $dateEnd, $data);
    }
    
    /**
     * Получение waybill из таблицы waybills в виде массива для JSON
     */
    private function getCruiseWaybillAsArray($cruiseId)
    {
        $waybills = $this->getCruiseWaybill($cruiseId);
        if (empty($waybills)) {
            return [];
        }
        
        $result = [];
        foreach ($waybills as $waybill) {
            $result[] = [
                'town_name' => $waybill['town_name'] ?? '',
                'town' => $waybill['town_id'] ?? 0,
                'arrival_time' => $waybill['arrival_time'] ?? null,
                'departure_time' => $waybill['departure_time'] ?? null,
                'bold' => $waybill['is_bold'] ?? 0
            ];
        }
        
        return $result;
    }

    /**
     * Batch сохранение круизов
     * Адаптирован для единого интерфейса UnifiedDatabase
     * Конвертирует данные из старого формата Gama в единый формат
     */
    public function saveCruisesBatch($cruises)
    {
        if (empty($cruises)) {
            return;
        }
        
        // Конвертируем круизы в единый формат
        $convertedCruises = [];
        foreach ($cruises as $cruise) {
            // Подготавливаем extra_data с path_s_id и path_f_id
            $extraData = [];
            if (isset($cruise['path_s_id'])) {
                $extraData['path_s_id'] = $cruise['path_s_id'];
            }
            if (isset($cruise['path_f_id'])) {
                $extraData['path_f_id'] = $cruise['path_f_id'];
            }
            
            // Получаем waybill_data (может быть JSON строка или массив)
            $waybillData = $cruise['waybill_data'] ?? '[]';
            if (is_string($waybillData)) {
                $decoded = json_decode($waybillData, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $waybillData = $decoded;
                } else {
                    $waybillData = [];
                }
            }
            
            $convertedCruises[] = [
                'id' => $cruise['gama_cruise_id'],
                'ship_id' => $cruise['gama_ship_id'],
                'name' => $cruise['name'],
                'date_start' => $cruise['date_start'],
                'date_end' => $cruise['date_end'],
                'route' => $cruise['route_name'] ?? null,
                'waybill_data' => $waybillData,
                'schedule_html' => $cruise['schedule_html'] ?? null,
                'extra_data' => !empty($extraData) ? $extraData : null
            ];
        }
        
        // Вызываем родительский метод с конвертированными данными
        parent::saveCruisesBatch($convertedCruises);
    }

    /**
     * Обновление waybill_data для круиза (исправление экранирования кириллицы)
     * Адаптирован для единой структуры
     */
    public function updateWaybillData($cruiseId, $waybillData)
    {
        try {
            // Если waybill_data это массив, конвертируем в JSON
            if (is_array($waybillData)) {
                $waybillData = json_encode($waybillData, JSON_UNESCAPED_UNICODE);
            }
            
            $stmt = $this->getPdo()->prepare("
                UPDATE cruises 
                SET waybill_data = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            return $stmt->execute([$waybillData, $cruiseId]);
        } catch (Exception $e) {
            throw new Exception("Ошибка обновления waybill_data для круиза $cruiseId: " . $e->getMessage());
        }
    }

    /**
     * Получение всех круизов с waybill_data
     * Адаптирован для единой структуры
     */
    public function getAllCruisesWithWaybill()
    {
        try {
            $stmt = $this->getPdo()->query("
                SELECT id, waybill_data 
                FROM cruises 
                WHERE waybill_data IS NOT NULL AND waybill_data != '' AND waybill_data != '[]'
            ");
            $cruises = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Декодируем waybill_data из JSON
            foreach ($cruises as &$cruise) {
                if (!empty($cruise['waybill_data'])) {
                    $decoded = json_decode($cruise['waybill_data'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $cruise['waybill_data'] = $decoded;
                    }
                }
            }
            
            return $cruises;
        } catch (Exception $e) {
            throw new Exception("Ошибка получения круизов: " . $e->getMessage());
        }
    }

    /**
     * Сохранение палубы (id = gama_deck_id)
     * Адаптирован для единого интерфейса UnifiedDatabase
     * Поддерживает старую сигнатуру для обратной совместимости
     */
    public function saveDeck($gamaDeckId, $name, $shipId = null, $data = [])
    {
        // Если $shipId передан как третий параметр (старый формат), используем его
        if ($shipId !== null && !is_array($shipId) && empty($data)) {
            return parent::saveDeck($gamaDeckId, $name, ['ship_id' => $shipId]);
        }
        // Если $shipId это массив (новый формат), используем его как $data
        if (is_array($shipId)) {
            return parent::saveDeck($gamaDeckId, $name, $shipId);
        }
        // Иначе используем стандартный формат
        return parent::saveDeck($gamaDeckId, $name, $data);
    }

    /**
     * Сохранение категории кают (id = gama_category_id)
     * Адаптирован для единого интерфейса UnifiedDatabase
     * Поддерживает старую сигнатуру для обратной совместимости
     */
    public function saveCabinCategory($gamaCategoryId, $name, $shipId = null, $data = [], $places = null, $deckId = null)
    {
        // Если используется старый формат (5 параметров: id, name, places, deckId, shipId)
        if (func_num_args() >= 5 && $shipId === null && empty($data) && $places !== null && $deckId !== null) {
            // Извлекаем параметры из старого формата
            $oldPlaces = func_get_arg(2);
            $oldDeckId = func_get_arg(3);
            $oldShipId = func_get_arg(4);
            
            if ($oldShipId === null) {
                throw new \Exception("ship_id обязателен для категории кают");
            }
            
            return parent::saveCabinCategory($gamaCategoryId, $name, $oldShipId, [
                'places' => $oldPlaces,
                'deck_id' => $oldDeckId
            ]);
        }
        
        // Новый формат - проверяем ship_id
        if ($shipId === null) {
            throw new \Exception("ship_id обязателен для категории кают");
        }
        
        // Если $data пустой, но есть дополнительные параметры
        if (empty($data) && $places !== null) {
            $data = [
                'places' => $places,
                'deck_id' => $deckId
            ];
        }
        
        return parent::saveCabinCategory($gamaCategoryId, $name, $shipId, $data);
    }

    /**
     * Сохранение цены (price_a → price_value, price_b → price_extra)
     * Адаптирован для единого интерфейса UnifiedDatabase
     */
    public function savePrice($cruiseId, $cabinCategoryId, $priceA, $priceB = null, $persons = null)
    {
        // Конвертируем price_a → price_value, price_b → price_extra
        // persons сохраняем в places_qnt или extra_data
        $extraData = null;
        if ($persons !== null) {
            $extraData = ['persons' => $persons];
        }
        
        return parent::savePrice($cruiseId, $cabinCategoryId, $priceA, [
            'price_extra' => $priceB,
            'places_qnt' => $persons ?? 1,
            'extra_data' => $extraData
        ]);
    }

    /**
     * Batch сохранение цен
     * Адаптирован для единого интерфейса UnifiedDatabase
     * Конвертирует price_a → price_value, price_b → price_extra
     */
    public function savePricesBatch($prices)
    {
        if (empty($prices)) {
            return;
        }
        
        // Конвертируем цены в единый формат
        $convertedPrices = [];
        foreach ($prices as $price) {
            $extraData = null;
            if (isset($price['persons'])) {
                $extraData = ['persons' => $price['persons']];
            }
            
            $convertedPrices[] = [
                'cruise_id' => $price['cruise_id'],
                'cabin_category_id' => $price['cabin_category_id'],
                'price_value' => $price['price_a'],
                'price_extra' => $price['price_b'] ?? null,
                'places_qnt' => $price['persons'] ?? 1,
                'extra_data' => $extraData
            ];
        }
        
        // Используем родительский метод
        parent::savePricesBatch($convertedPrices);
    }

    /**
     * Сохранение путевого листа
     * Сохраняет в старую таблицу waybills для обратной совместимости
     * При сохранении круиза waybills автоматически конвертируются в JSON
     */
    public function saveWaybill($cruiseId, $townName, $townId, $arrivalTime, $departureTime, $isBold, $orderIndex)
    {
        // Создаем таблицу waybills если её нет (для обратной совместимости)
        if (!$this->tableExists('waybills')) {
            $this->getPdo()->exec("
                CREATE TABLE IF NOT EXISTS waybills (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    cruise_id INTEGER,
                    town_name TEXT,
                    town_id INTEGER,
                    arrival_time DATETIME,
                    departure_time DATETIME,
                    is_bold INTEGER DEFAULT 0,
                    order_index INTEGER,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
            $this->getPdo()->exec("CREATE INDEX IF NOT EXISTS idx_waybills_cruise_id ON waybills(cruise_id)");
        }
        
        $stmt = $this->getPdo()->prepare("
            INSERT INTO waybills 
            (cruise_id, town_name, town_id, arrival_time, departure_time, is_bold, order_index) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$cruiseId, $townName, $townId, $arrivalTime, $departureTime, $isBold, $orderIndex]);
    }

    /**
     * Получение всех круизов с теплоходами
     * Адаптирован для единой структуры с извлечением path_s_id/path_f_id из extra_data
     */
    public function getAllCruises()
    {
        $cruises = parent::getAllCruises();
        
        // Преобразуем данные для обратной совместимости
        foreach ($cruises as &$cruise) {
            // Извлекаем path_s_id и path_f_id из extra_data
            if (!empty($cruise['extra_data']) && is_array($cruise['extra_data'])) {
                $cruise['path_s_id'] = $cruise['extra_data']['path_s_id'] ?? null;
                $cruise['path_f_id'] = $cruise['extra_data']['path_f_id'] ?? null;
            }
            
            // Добавляем обратную совместимость с route_name
            if (!isset($cruise['route_name']) && isset($cruise['route'])) {
                $cruise['route_name'] = $cruise['route'];
            }
            
            // Добавляем gama_cruise_id и gama_ship_id для обратной совместимости
            $cruise['gama_cruise_id'] = $cruise['id'];
            $cruise['gama_ship_id'] = $cruise['ship_id'];
        }
        
        return $cruises;
    }

    /**
     * Получение круиза по ID
     * Адаптирован для единой структуры с извлечением path_s_id/path_f_id из extra_data
     */
    public function getCruiseById($cruiseId)
    {
        $cruise = parent::getCruiseById($cruiseId);
        
        if ($cruise) {
            // Извлекаем path_s_id и path_f_id из extra_data
            if (!empty($cruise['extra_data']) && is_array($cruise['extra_data'])) {
                $cruise['path_s_id'] = $cruise['extra_data']['path_s_id'] ?? null;
                $cruise['path_f_id'] = $cruise['extra_data']['path_f_id'] ?? null;
            }
            
            // Добавляем обратную совместимость с route_name
            if (!isset($cruise['route_name']) && isset($cruise['route'])) {
                $cruise['route_name'] = $cruise['route'];
            }
            
            // Добавляем gama_cruise_id и gama_ship_id для обратной совместимости
            $cruise['gama_cruise_id'] = $cruise['id'];
            $cruise['gama_ship_id'] = $cruise['ship_id'];
        }
        
        return $cruise;
    }

    /**
     * Получение цен для круиза
     * Адаптирован для единой структуры с конвертацией price_value → price_a
     */
    public function getCruisePrices($cruiseId)
    {
        $prices = parent::getPricesByCruiseId($cruiseId);
        
        // Преобразуем для обратной совместимости
        foreach ($prices as &$price) {
            // Конвертируем price_value → price_a, price_extra → price_b
            $price['price_a'] = $price['price_value'] ?? null;
            $price['price_b'] = $price['price_extra'] ?? null;
            
            // Извлекаем persons из extra_data или используем places_qnt
            if (!empty($price['extra_data']) && is_array($price['extra_data']) && isset($price['extra_data']['persons'])) {
                $price['persons'] = $price['extra_data']['persons'];
            } else {
                $price['persons'] = $price['places_qnt'] ?? 1;
            }
        }
        
        // Сортируем по price_a
        usort($prices, function($a, $b) {
            $priceA = $a['price_a'] ?? 0;
            $priceB = $b['price_a'] ?? 0;
            return $priceA <=> $priceB;
        });
        
        return $prices;
    }

    /**
     * Получение цен для круиза по ID (для проверки наличия цен)
     * Адаптирован для единой структуры с конвертацией price_value → price_a
     */
    public function getPricesByCruiseId($cruiseId)
    {
        $prices = parent::getPricesByCruiseId($cruiseId);
        
        // Преобразуем для обратной совместимости
        foreach ($prices as &$price) {
            // Конвертируем price_value → price_a, price_extra → price_b
            $price['price_a'] = $price['price_value'] ?? null;
            $price['price_b'] = $price['price_extra'] ?? null;
            
            // Извлекаем persons из extra_data или используем places_qnt
            if (!empty($price['extra_data']) && is_array($price['extra_data']) && isset($price['extra_data']['persons'])) {
                $price['persons'] = $price['extra_data']['persons'];
            } else {
                $price['persons'] = $price['places_qnt'] ?? 1;
            }
        }
        
        return $prices;
    }

    /**
     * Получение путевого листа для круиза
     * Поддерживает старую таблицу waybills для обратной совместимости
     */
    public function getCruiseWaybill($cruiseId)
    {
        // Сначала пытаемся получить из таблицы waybills (старый формат)
        if ($this->tableExists('waybills')) {
            $stmt = $this->getPdo()->prepare("
                SELECT * FROM waybills 
                WHERE cruise_id = ? 
                ORDER BY order_index
            ");
            $stmt->execute([$cruiseId]);
            $waybills = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($waybills)) {
                return $waybills;
            }
        }
        
        // Если в таблице waybills нет данных, пытаемся получить из waybill_data круиза
        $cruise = parent::getCruiseById($cruiseId);
        if ($cruise && !empty($cruise['waybill_data']) && is_array($cruise['waybill_data'])) {
            // Конвертируем JSON waybill_data в формат таблицы waybills
            $result = [];
            foreach ($cruise['waybill_data'] as $index => $point) {
                $result[] = [
                    'id' => null,
                    'cruise_id' => $cruiseId,
                    'town_name' => $point['town_name'] ?? '',
                    'town_id' => $point['town'] ?? 0,
                    'arrival_time' => $point['arrival_time'] ?? null,
                    'departure_time' => $point['departure_time'] ?? null,
                    'is_bold' => $point['bold'] ?? 0,
                    'order_index' => $index
                ];
            }
            return $result;
        }
        
        return [];
    }

    /**
     * Получение статистики
     * Использует родительский метод
     */
    public function getStats()
    {
        return parent::getStats();
    }

    /**
     * Очистка всех данных
     * Включает очистку старой таблицы waybills
     */
    public function clearAll()
    {
        // Очищаем старую таблицу waybills если она существует
        if ($this->tableExists('waybills')) {
            $this->getPdo()->exec("DELETE FROM waybills");
        }
        
        // Вызываем родительский метод для очистки основных таблиц
        parent::clearAll();
    }

    /**
     * Получение теплохода по Gama ID
     */
    public function getShipByGamaId($gamaShipId)
    {
        return parent::getShipBySourceId($gamaShipId);
    }
    
    /**
     * Получение круиза по gama_cruise_id
     */
    public function getCruiseByGamaId($gamaCruiseId)
    {
        return $this->getCruiseById($gamaCruiseId);
    }

    /**
     * Получение количества цен для круиза
     */
    public function getCruisePricesCount($cruiseId)
    {
        $stmt = $this->getPdo()->prepare("SELECT COUNT(*) FROM prices WHERE cruise_id = ?");
        $stmt->execute([$cruiseId]);
        return $stmt->fetchColumn();
    }

    /**
     * Получение всех теплоходов
     * Использует родительский метод
     */
    public function getAllShips()
    {
        return parent::getAllShips();
    }

    /**
     * Получение всех категорий кают
     * Использует родительский метод
     */
    public function getAllCabinCategories()
    {
        return parent::getAllCabinCategories();
    }

    /**
     * Получение всех цен
     */
    public function getAllPrices()
    {
        $stmt = $this->getPdo()->prepare("SELECT * FROM prices ORDER BY cruise_id, cabin_category_id");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Получение всех путевых листов
     */
    public function getAllWaybills()
    {
        if (!$this->tableExists('waybills')) {
            return [];
        }
        $stmt = $this->getPdo()->prepare("SELECT * FROM waybills ORDER BY cruise_id, order_index");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Удаление круиза и всех связанных данных
     */
    public function deleteCruise($gamaCruiseId)
    {
        try {
            // Начинаем транзакцию
            $this->getPdo()->beginTransaction();
            
            // Удаляем цены
            $stmt = $this->getPdo()->prepare("DELETE FROM prices WHERE cruise_id = ?");
            $stmt->execute([$gamaCruiseId]);
            
            // Удаляем путевые листы (если таблица существует)
            if ($this->tableExists('waybills')) {
                $stmt = $this->getPdo()->prepare("DELETE FROM waybills WHERE cruise_id = ?");
                $stmt->execute([$gamaCruiseId]);
            }
            
            // Удаляем сам круиз
            $stmt = $this->getPdo()->prepare("DELETE FROM cruises WHERE id = ?");
            $stmt->execute([$gamaCruiseId]);
            
            // Подтверждаем транзакцию
            $this->getPdo()->commit();
            
            return true;
            
        } catch (\Exception $e) {
            // Откатываем транзакцию при ошибке
            $this->getPdo()->rollBack();
            throw $e;
        }
    }

    /**
     * Очистка круизов без цен (вызывается в конце фазы 1)
     */
    public function cleanCruisesWithoutPrices()
    {
        try {
            // Получаем все круизы
            $cruises = $this->getAllCruises();
            $totalCruises = count($cruises);
            $deletedCount = 0;
            
            foreach ($cruises as $cruise) {
                $cruiseId = $cruise['id'];
                
                // Проверяем, есть ли цены для этого круиза
                $prices = $this->getPricesByCruiseId($cruiseId);
                
                if (empty($prices)) {
                    // Удаляем круиз и связанные данные
                    $this->deleteCruise($cruiseId);
                    $deletedCount++;
                }
            }
            
            return [
                'total' => $totalCruises,
                'deleted' => $deletedCount,
                'remaining' => $totalCruises - $deletedCount
            ];
            
        } catch (\Exception $e) {
            throw new \Exception("Ошибка при очистке круизов без цен: " . $e->getMessage());
        }
    }
}
