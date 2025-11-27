<?php namespace Zen\Worker\Console\infoflot;

use PDO;
use Exception;
use Zen\Worker\Console\unified\UnifiedDatabase;

/**
 * InfoflotDatabase - наследуется от UnifiedDatabase
 * Использует единую структуру SQLite для всех источников
 */
class InfoflotDatabase extends UnifiedDatabase
{
    /**
     * Конструктор - передает путь к базе данных в родительский класс
     */
    public function __construct()
    {
        $dbPath = __DIR__ . '/infoflot_data.sqlite';
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
        // Миграция таблицы ships - уже есть все нужные поля
        
        // Миграция таблицы decks - уже есть все нужные поля
        
        // Миграция таблицы cabin_categories
        $this->addColumnIfNotExists('cabin_categories', 'description', 'TEXT');
        $this->addColumnIfNotExists('cabin_categories', 'places_extra', 'INTEGER');
        $this->addColumnIfNotExists('cabin_categories', 'extra_data', 'TEXT');
        
        // Миграция таблицы cruises
        $this->addColumnIfNotExists('cruises', 'schedule_html', 'TEXT');
        $this->addColumnIfNotExists('cruises', 'waybill_data', 'TEXT');
        $this->addColumnIfNotExists('cruises', 'extra_data', 'TEXT');
        
        // Миграция таблицы prices
        $this->addColumnIfNotExists('prices', 'price_value', 'INTEGER');
        $this->addColumnIfNotExists('prices', 'price_extra', 'INTEGER');
        $this->addColumnIfNotExists('prices', 'places_qnt', 'INTEGER DEFAULT 1');
        $this->addColumnIfNotExists('prices', 'nofull', 'INTEGER DEFAULT 0');
        $this->addColumnIfNotExists('prices', 'deck_id', 'INTEGER');
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
     * Сохранение теплохода (id = infoflot_ship_id)
     * Адаптирован для единого интерфейса UnifiedDatabase
     */
    public function saveShip($infoflotShipId, $name, $type = null, $operatorName = null, $description = '')
    {
        // Если используется старый формат (5 параметров)
        if ($type !== null && !is_array($type) && $operatorName !== null && !is_array($operatorName) && $description !== '' && !is_array($description)) {
            return parent::saveShip($infoflotShipId, $name, [
                'type' => $type,
                'operator_name' => $operatorName,
                'description' => $description
            ]);
        }
        
        // Новый формат - вызываем родительский метод напрямую
        return parent::saveShip($infoflotShipId, $name, is_array($type) ? $type : []);
    }

    /**
     * Batch сохранение теплоходов
     * Адаптирован для единого интерфейса UnifiedDatabase
     */
    public function saveShipsBatch($ships)
    {
        // Конвертируем в единый формат
        $convertedShips = [];
        foreach ($ships as $ship) {
            $convertedShips[] = [
                'id' => $ship['id'],
                'name' => $ship['name'],
                'type' => $ship['type'] ?? null,
                'operator_name' => $ship['operator_name'] ?? null,
                'description' => $ship['description'] ?? ''
            ];
        }
        
        // Используем родительский метод
        parent::saveShipsBatch($convertedShips);
    }

    /**
     * Получение теплохода по Infoflot ID
     */
    public function getShipByInfoflotId($infoflotShipId)
    {
        return parent::getShipBySourceId($infoflotShipId);
    }

    /**
     * Сохранение палубы (id = infoflot_deck_id)
     * Адаптирован для единого интерфейса UnifiedDatabase
     */
    public function saveDeck($infoflotDeckId, $name, $shipId = null, $position = null, $data = [])
    {
        // Если используется старый формат (4 параметра)
        if ($shipId !== null && !is_array($shipId) && $position !== null && !is_array($position) && empty($data)) {
            return parent::saveDeck($infoflotDeckId, $name, [
                'ship_id' => $shipId,
                'position' => $position
            ]);
        }
        
        // Новый формат
        if (is_array($shipId)) {
            return parent::saveDeck($infoflotDeckId, $name, $shipId);
        }
        
        // Стандартный формат UnifiedDatabase
        return parent::saveDeck($infoflotDeckId, $name, $data);
    }

    /**
     * Сохранение категории кают (id = infoflot_type_id)
     * Адаптирован для единого интерфейса UnifiedDatabase
     */
    public function saveCabinCategory($infoflotTypeId, $name, $shipId = null, $data = [], $places = null, $deckId = null)
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
            
            return parent::saveCabinCategory($infoflotTypeId, $name, $oldShipId, [
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
        
        return parent::saveCabinCategory($infoflotTypeId, $name, $shipId, $data);
    }

    /**
     * Сохранение круиза (id = infoflot_cruise_id, ship_id = infoflot_ship_id)
     * Адаптирован для единого интерфейса UnifiedDatabase
     * Поддерживает старую сигнатуру для обратной совместимости
     */
    public function saveCruise($idOrData, $shipId = null, $name = null, $dateStart = null, $dateEnd = null, $data = [])
    {
        // Если первый параметр - массив (старый формат)
        if (is_array($idOrData)) {
            $cruiseData = $idOrData;
            
            // Создаем waybill_data из route (если есть)
            $waybillData = $this->createWaybillDataFromRoute($cruiseData['route'] ?? '');
            
            // Формируем extra_data из дополнительных полей
            $extraData = [];
            if (isset($cruiseData['beautiful_name'])) {
                $extraData['beautiful_name'] = $cruiseData['beautiful_name'];
            }
            if (isset($cruiseData['route_short'])) {
                $extraData['route_short'] = $cruiseData['route_short'];
            }
            if (isset($cruiseData['date_start_timestamp'])) {
                $extraData['date_start_timestamp'] = $cruiseData['date_start_timestamp'];
            }
            if (isset($cruiseData['date_end_timestamp'])) {
                $extraData['date_end_timestamp'] = $cruiseData['date_end_timestamp'];
            }
            
            // Вызываем родительский метод с единым интерфейсом
            return parent::saveCruise(
                $cruiseData['infoflot_cruise_id'],
                $cruiseData['infoflot_ship_id'],
                $cruiseData['name'] ?? '',
                $cruiseData['date_start'] ?? '',
                $cruiseData['date_end'] ?? '',
                [
                    'days' => $cruiseData['days'] ?? null,
                    'nights' => $cruiseData['nights'] ?? null,
                    'description' => $cruiseData['description'] ?? null,
                    'waybill_data' => $waybillData,
                    'extra_data' => !empty($extraData) ? $extraData : null
                ]
            );
        }
        
        // Новый формат - вызываем родительский метод напрямую
        return parent::saveCruise($idOrData, $shipId, $name, $dateStart, $dateEnd, $data);
    }
    
    /**
     * Создание waybill_data из route (текстовое описание маршрута)
     * Infoflot не имеет структурированного маршрута, поэтому создаем простой формат
     */
    private function createWaybillDataFromRoute($route)
    {
        if (empty($route)) {
            return [];
        }
        
        // Сохраняем route как текстовое описание в waybill_data
        return [
            [
                'town' => null,
                'town_name' => $route,
                'excursion' => '',
                'bold' => 0
            ]
        ];
    }

    /**
     * Batch сохранение круизов
     * Адаптирован для единого интерфейса UnifiedDatabase
     */
    public function saveCruisesBatch($cruises)
    {
        if (empty($cruises)) {
            return;
        }
        
        // Конвертируем круизы в единый формат
        $convertedCruises = [];
        foreach ($cruises as $cruise) {
            // Создаем waybill_data из route
            $waybillData = $this->createWaybillDataFromRoute($cruise['route'] ?? '');
            
            // Формируем extra_data из дополнительных полей
            $extraData = [];
            if (isset($cruise['beautiful_name'])) {
                $extraData['beautiful_name'] = $cruise['beautiful_name'];
            }
            if (isset($cruise['route_short'])) {
                $extraData['route_short'] = $cruise['route_short'];
            }
            if (isset($cruise['date_start_timestamp'])) {
                $extraData['date_start_timestamp'] = $cruise['date_start_timestamp'];
            }
            if (isset($cruise['date_end_timestamp'])) {
                $extraData['date_end_timestamp'] = $cruise['date_end_timestamp'];
            }
            
            $convertedCruises[] = [
                'id' => $cruise['infoflot_cruise_id'],
                'ship_id' => $cruise['infoflot_ship_id'],
                'name' => $cruise['name'] ?? '',
                'date_start' => $cruise['date_start'] ?? '',
                'date_end' => $cruise['date_end'] ?? '',
                'days' => $cruise['days'] ?? null,
                'nights' => $cruise['nights'] ?? null,
                'description' => $cruise['description'] ?? null,
                'waybill_data' => $waybillData,
                'extra_data' => !empty($extraData) ? $extraData : null
            ];
        }
        
        // Вызываем родительский метод с конвертированными данными
        parent::saveCruisesBatch($convertedCruises);
    }

    /**
     * Сохранение цены
     * Адаптирован для единого интерфейса UnifiedDatabase
     * Конвертирует price_adult/price_default → price_value/price_extra
     */
    public function savePrice($cruiseId, $cabinCategoryId, $priceValue, $data = [], $typeName = null, $priceAdult = null, $priceDefault = null)
    {
        // Если используется старый формат (typeName, priceAdult, priceDefault переданы)
        if (func_num_args() >= 5 && $typeName !== null && $priceAdult !== null && empty($data)) {
            // Конвертируем price_adult → price_value, price_default → price_extra
            // type_name сохраняем в extra_data
            $extraData = ['type_name' => $typeName];
            
            return parent::savePrice($cruiseId, $cabinCategoryId, $priceAdult, [
                'price_extra' => $priceDefault,
                'extra_data' => $extraData
            ]);
        }
        
        // Новый формат - вызываем родительский метод напрямую
        return parent::savePrice($cruiseId, $cabinCategoryId, $priceValue, $data);
    }

    /**
     * Batch сохранение цен
     * Адаптирован для единого интерфейса UnifiedDatabase
     */
    public function savePricesBatch($prices)
    {
        if (empty($prices)) {
            return;
        }
        
        // Конвертируем цены в единый формат
        $convertedPrices = [];
        foreach ($prices as $price) {
            // Конвертируем price_adult → price_value, price_default → price_extra
            // type_name сохраняем в extra_data
            $extraData = [];
            if (isset($price['type_name'])) {
                $extraData['type_name'] = $price['type_name'];
            }
            
            $convertedPrices[] = [
                'cruise_id' => $price['cruise_id'],
                'cabin_category_id' => $price['cabin_category_id'],
                'price_value' => $price['price_adult'],
                'price_extra' => $price['price_default'] ?? null,
                'extra_data' => !empty($extraData) ? $extraData : null
            ];
        }
        
        // Используем родительский метод
        parent::savePricesBatch($convertedPrices);
    }


    /**
     * Получение всех круизов с теплоходами
     * Адаптирован для единой структуры
     */
    public function getAllCruises()
    {
        $cruises = parent::getAllCruises();
        
        // Преобразуем данные для обратной совместимости
        foreach ($cruises as &$cruise) {
            // Добавляем infoflot_cruise_id и infoflot_ship_id для обратной совместимости
            $cruise['infoflot_cruise_id'] = $cruise['id'];
            $cruise['infoflot_ship_id'] = $cruise['ship_id'];
            
            // Извлекаем дополнительные поля из extra_data
            if (!empty($cruise['extra_data'])) {
                $extraData = is_string($cruise['extra_data']) 
                    ? json_decode($cruise['extra_data'], true) 
                    : $cruise['extra_data'];
                
                if (is_array($extraData)) {
                    if (isset($extraData['beautiful_name'])) {
                        $cruise['beautiful_name'] = $extraData['beautiful_name'];
                    }
                    if (isset($extraData['route_short'])) {
                        $cruise['route_short'] = $extraData['route_short'];
                    }
                    if (isset($extraData['date_start_timestamp'])) {
                        $cruise['date_start_timestamp'] = $extraData['date_start_timestamp'];
                    }
                    if (isset($extraData['date_end_timestamp'])) {
                        $cruise['date_end_timestamp'] = $extraData['date_end_timestamp'];
                    }
                }
            }
        }
        
        return $cruises;
    }

    /**
     * Получение цен для круиза по ID
     * Адаптирован для единой структуры
     */
    public function getPricesByCruiseId($cruiseId)
    {
        $prices = parent::getPricesByCruiseId($cruiseId);
        
        // Преобразуем данные для обратной совместимости
        foreach ($prices as &$price) {
            // Конвертируем price_value → price_adult, price_extra → price_default
            if (isset($price['price_value'])) {
                $price['price_adult'] = $price['price_value'];
            }
            if (isset($price['price_extra'])) {
                $price['price_default'] = $price['price_extra'];
            }
            
            // Извлекаем type_name из extra_data
            if (!empty($price['extra_data'])) {
                $extraData = is_string($price['extra_data']) 
                    ? json_decode($price['extra_data'], true) 
                    : $price['extra_data'];
                
                if (is_array($extraData) && isset($extraData['type_name'])) {
                    $price['type_name'] = $extraData['type_name'];
                }
            }
        }
        
        return $prices;
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
     * Использует родительский метод
     */
    public function clearAll()
    {
        return parent::clearAll();
    }

    /**
     * Получение всех теплоходов
     */
    public function getAllShips()
    {
        $stmt = $this->getPdo()->prepare("SELECT * FROM ships ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Обновление deck_id в cabin_categories на основе данных из кают
     * Используется для восстановления связей после обработки круизов
     */
    public function updateCabinCategoriesDeckId($typeToDeckMap)
    {
        if (empty($typeToDeckMap)) {
            return 0;
        }
        
        try {
            $this->getPdo()->beginTransaction();
            
            $stmt = $this->getPdo()->prepare("
                UPDATE cabin_categories 
                SET deck_id = ? 
                WHERE id = ? AND (deck_id IS NULL OR deck_id = 0)
            ");
            
            $updated = 0;
            foreach ($typeToDeckMap as $typeId => $deckId) {
                if ($deckId !== null && $deckId > 0) {
                    $stmt->execute([$deckId, $typeId]);
                    $updated += $stmt->rowCount();
                }
            }
            
            $this->getPdo()->commit();
            
            return $updated;
        } catch (\Exception $e) {
            $this->getPdo()->rollBack();
            throw $e;
        }
    }

    /**
     * Очистка круизов без цен (вызывается в конце фазы 1)
     * Использует родительский метод
     */
    public function cleanCruisesWithoutPrices()
    {
        return parent::cleanCruisesWithoutPrices();
    }
}

