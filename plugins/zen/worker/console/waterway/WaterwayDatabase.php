<?php namespace Zen\Worker\Console\waterway;

use Exception;
use Zen\Worker\Console\unified\UnifiedDatabase;
use Zen\Worker\Console\transfer\TransferConfig;

/**
 * WaterwayDatabase - наследуется от UnifiedDatabase
 * Использует единую структуру SQLite для всех источников
 */
class WaterwayDatabase extends UnifiedDatabase
{
    /**
     * Конструктор - передает путь к базе данных в родительский класс
     */
    public function __construct()
    {
        // Получаем путь к базе данных из конфигурации
        $dbPath = TransferConfig::getDbPath('waterway');
        if (!file_exists($dbPath)) {
            throw new Exception("База данных waterway_data.sqlite не найдена: {$dbPath}");
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
        
        // Добавляем поля для обратной совместимости
        try {
            $this->getPdo()->exec("ALTER TABLE cruises ADD COLUMN date_start_precise DATETIME");
        } catch (\Exception $e) {
            // Поле уже существует, игнорируем ошибку
        }
        
        try {
            $this->getPdo()->exec("ALTER TABLE cruises ADD COLUMN date_end_precise DATETIME");
        } catch (\Exception $e) {
            // Поле уже существует, игнорируем ошибку
        }
    }
    
    /**
     * Миграция структуры таблиц для единой схемы
     * Добавляет недостающие поля из UnifiedDatabase
     */
    private function migrateTableStructure()
    {
        // Миграция таблицы ships
        $this->addColumnIfNotExists('ships', 'operator_name', 'TEXT');
        $this->addColumnIfNotExists('ships', 'extra_data', 'TEXT');
        
        // Миграция таблицы decks
        $this->addColumnIfNotExists('decks', 'position', 'INTEGER');
        $this->addColumnIfNotExists('decks', 'extra_data', 'TEXT');
        
        // Миграция таблицы cabin_categories
        $this->addColumnIfNotExists('cabin_categories', 'places', 'INTEGER DEFAULT 1');
        $this->addColumnIfNotExists('cabin_categories', 'places_extra', 'INTEGER');
        $this->addColumnIfNotExists('cabin_categories', 'extra_data', 'TEXT');
        
        // Миграция таблицы cruises
        $this->addColumnIfNotExists('cruises', 'days', 'INTEGER');
        $this->addColumnIfNotExists('cruises', 'nights', 'INTEGER');
        $this->addColumnIfNotExists('cruises', 'description', 'TEXT');
        $this->addColumnIfNotExists('cruises', 'extra_data', 'TEXT');
        
        // Миграция таблицы prices
        $this->addColumnIfNotExists('prices', 'price_extra', 'INTEGER');
        $this->addColumnIfNotExists('prices', 'places_qnt', 'INTEGER DEFAULT 1');
        $this->addColumnIfNotExists('prices', 'nofull', 'INTEGER DEFAULT 0');
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
            $columns = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
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
            // Игнорируем ошибки при добавлении колонки
        }
    }

    /**
     * Сохранение теплохода (id = waterway_ship_id)
     * Адаптирован для использования единого интерфейса UnifiedDatabase
     */
    public function saveShip($waterwayShipId, $name, $type = null, $description = '')
    {
        return parent::saveShip($waterwayShipId, $name, [
            'type' => $type,
            'description' => $description
        ]);
    }

    /**
     * Batch сохранение теплоходов
     * Адаптирован для использования единого интерфейса UnifiedDatabase
     */
    public function saveShipsBatch($ships)
    {
        // Преобразуем формат данных для единого интерфейса
        $normalizedShips = [];
        foreach ($ships as $ship) {
            $normalizedShips[] = [
                'id' => $ship['id'],
                'name' => $ship['name'],
                'type' => $ship['type'] ?? null,
                'description' => $ship['description'] ?? ''
            ];
        }
        
        parent::saveShipsBatch($normalizedShips);
    }

    /**
     * Получение теплохода по Waterway ID
     * Алиас для getShipBySourceId для обратной совместимости
     */
    public function getShipByWaterwayId($waterwayShipId)
    {
        return $this->getShipBySourceId($waterwayShipId);
    }

    /**
     * Сохранение круиза (id = waterway_cruise_id, ship_id = waterway_ship_id)
     * Адаптирован для использования единого интерфейса UnifiedDatabase
     * Конвертирует date_start_precise/date_end_precise → date_start/date_end
     * 
     * Поддерживает два варианта вызова:
     * 1. Старый: saveCruise($data) - для обратной совместимости
     * 2. Новый: saveCruise($id, $shipId, $name, $dateStart, $dateEnd, $data = []) - единый интерфейс
     */
    public function saveCruise($id, $shipId = null, $name = null, $dateStart = null, $dateEnd = null, $data = [])
    {
        // Определяем, какой вариант вызова используется
        // Если первый параметр - массив, это старый формат
        if (is_array($id) && $shipId === null) {
            $data = $id;
            $cruiseId = $data['waterway_cruise_id'];
            $shipId = $data['waterway_ship_id'];
            $name = $data['name'] ?? '';
            
            // Используем date_start_precise/date_end_precise как основные даты
            // Если их нет, используем date_start/date_end
            $dateStart = $data['date_start_precise'] ?? $data['date_start'] ?? null;
            $dateEnd = $data['date_end_precise'] ?? $data['date_end'] ?? null;
        } else {
            // Новый формат - параметры уже переданы
            $cruiseId = $id;
        }
        
        if (!$dateStart || !$dateEnd) {
            throw new \Exception("date_start и date_end обязательны для круиза");
        }
        
        // Подготавливаем waybill_data
        $waybillData = null;
        if (!empty($data['waybill_data'])) {
            if (is_string($data['waybill_data'])) {
                $waybillData = json_decode($data['waybill_data'], true);
            } else {
                $waybillData = $data['waybill_data'];
            }
        }
        
        // Сохраняем специфичные поля в extra_data
        $extraData = [];
        if (!empty($data['description'])) {
            $extraData['description'] = $data['description'];
        }
        if (!empty($data['date_start']) && is_array($id)) {
            $extraData['date_start'] = $data['date_start'];
        }
        if (!empty($data['date_end']) && is_array($id)) {
            $extraData['date_end'] = $data['date_end'];
        }
        if (!empty($data['date_start_precise'])) {
            $extraData['date_start_precise'] = $data['date_start_precise'];
        }
        if (!empty($data['date_end_precise'])) {
            $extraData['date_end_precise'] = $data['date_end_precise'];
        }
        
        // Сохраняем через родительский метод
        $result = parent::saveCruise($cruiseId, $shipId, $name, $dateStart, $dateEnd, [
            'route' => $data['route'] ?? null,
            'days' => $data['days'] ?? null,
            'waybill_data' => $waybillData,
            'schedule_html' => $data['schedule_html'] ?? null,
            'extra_data' => !empty($extraData) ? $extraData : null
        ]);
        
        // Сохраняем date_start_precise и date_end_precise для обратной совместимости
        if ($result && is_array($id) && (!empty($data['date_start_precise']) || !empty($data['date_end_precise']))) {
            $stmt = $this->getPdo()->prepare("
                UPDATE cruises 
                SET date_start_precise = ?, date_end_precise = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $data['date_start_precise'] ?? null,
                $data['date_end_precise'] ?? null,
                $cruiseId
            ]);
        }
        
        return $result;
    }

    /**
     * Batch сохранение круизов
     * Адаптирован для использования единого интерфейса UnifiedDatabase
     */
    public function saveCruisesBatch($cruises)
    {
        // Преобразуем формат данных для единого интерфейса
        $normalizedCruises = [];
        foreach ($cruises as $cruise) {
            $cruiseId = $cruise['waterway_cruise_id'];
            $shipId = $cruise['waterway_ship_id'];
            $name = $cruise['name'] ?? '';
            
            // Используем date_start_precise/date_end_precise как основные даты
            $dateStart = $cruise['date_start_precise'] ?? $cruise['date_start'] ?? null;
            $dateEnd = $cruise['date_end_precise'] ?? $cruise['date_end'] ?? null;
            
            if (!$dateStart || !$dateEnd) {
                continue; // Пропускаем круизы без дат
            }
            
            // Подготавливаем waybill_data
            $waybillData = null;
            if (!empty($cruise['waybill_data'])) {
                if (is_string($cruise['waybill_data'])) {
                    $waybillData = json_decode($cruise['waybill_data'], true);
                } else {
                    $waybillData = $cruise['waybill_data'];
                }
            }
            
            // Сохраняем специфичные поля в extra_data
            $extraData = [];
            if (!empty($cruise['description'])) {
                $extraData['description'] = $cruise['description'];
            }
            if (!empty($cruise['date_start'])) {
                $extraData['date_start'] = $cruise['date_start'];
            }
            if (!empty($cruise['date_end'])) {
                $extraData['date_end'] = $cruise['date_end'];
            }
            
            $normalizedCruises[] = [
                'id' => $cruiseId,
                'ship_id' => $shipId,
                'name' => $name,
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
                'route' => $cruise['route'] ?? null,
                'days' => $cruise['days'] ?? null,
                'waybill_data' => $waybillData,
                'schedule_html' => $cruise['schedule_html'] ?? null,
                'extra_data' => !empty($extraData) ? $extraData : null,
                // Сохраняем для последующего обновления date_start_precise/date_end_precise
                '_date_start_precise' => $cruise['date_start_precise'] ?? null,
                '_date_end_precise' => $cruise['date_end_precise'] ?? null
            ];
        }
        
        // Сохраняем через родительский метод
        parent::saveCruisesBatch($normalizedCruises);
        
        // Обновляем date_start_precise и date_end_precise для обратной совместимости
        $this->getPdo()->beginTransaction();
        try {
            $stmt = $this->getPdo()->prepare("
                UPDATE cruises 
                SET date_start_precise = ?, date_end_precise = ?
                WHERE id = ?
            ");
            
            foreach ($cruises as $cruise) {
                if (!empty($cruise['date_start_precise']) || !empty($cruise['date_end_precise'])) {
                    $stmt->execute([
                        $cruise['date_start_precise'] ?? null,
                        $cruise['date_end_precise'] ?? null,
                        $cruise['waterway_cruise_id']
                    ]);
                }
            }
            
            $this->getPdo()->commit();
        } catch (\Exception $e) {
            $this->getPdo()->rollBack();
            throw $e;
        }
    }

    /**
     * Сохранение категории кают (id = waterway roomClass id)
     * Адаптирован для использования единого интерфейса UnifiedDatabase
     * Поддерживает старую сигнатуру для обратной совместимости
     */
    public function saveCabinCategory($waterwayCategoryId, $name, $shipId = null, $data = [], $description = null, $metaId = null, $metaName = null, $deckId = null)
    {
        // Если используется старый формат (7 параметров)
        if (func_num_args() >= 7 && $shipId === null && empty($data)) {
            // Извлекаем параметры из старого формата
            $oldShipId = func_get_arg(5); // 6-й параметр (индекс 5)
            $oldDeckId = func_get_arg(6); // 7-й параметр (индекс 6)
            $oldDescription = func_get_arg(2);
            $oldMetaId = func_get_arg(3);
            $oldMetaName = func_get_arg(4);
            
            if (!$oldShipId) {
                throw new \Exception("ship_id обязателен для категории кают");
            }
            
            return parent::saveCabinCategory($waterwayCategoryId, $name, $oldShipId, [
                'description' => $oldDescription,
                'meta_id' => $oldMetaId,
                'meta_name' => $oldMetaName,
                'deck_id' => $oldDeckId,
                'places' => 1 // По умолчанию для Waterway
            ]);
        }
        
        // Новый формат - проверяем ship_id
        if (!$shipId) {
            throw new \Exception("ship_id обязателен для категории кают");
        }
        
        // Если $data пустой, но есть дополнительные параметры
        if (empty($data) && $description !== null) {
            $data = [
                'description' => $description,
                'meta_id' => $metaId,
                'meta_name' => $metaName,
                'deck_id' => $deckId,
                'places' => 1
            ];
        }
        
        return parent::saveCabinCategory($waterwayCategoryId, $name, $shipId, $data);
    }

    /**
     * Batch сохранение категорий кают
     * Адаптирован для использования единого интерфейса UnifiedDatabase
     */
    public function saveCabinCategoriesBatch($categories)
    {
        // Преобразуем формат данных для единого интерфейса
        $normalizedCategories = [];
        foreach ($categories as $category) {
            if (empty($category['ship_id'])) {
                continue; // Пропускаем категории без ship_id
            }
            
            $normalizedCategories[] = [
                'id' => $category['id'],
                'name' => $category['name'],
                'ship_id' => $category['ship_id'],
                'description' => $category['description'] ?? null,
                'meta_id' => $category['meta_id'] ?? null,
                'meta_name' => $category['meta_name'] ?? null,
                'deck_id' => $category['deck_id'] ?? null,
                'places' => 1 // По умолчанию для Waterway
            ];
        }
        
        parent::saveCabinCategoriesBatch($normalizedCategories);
    }

    /**
     * Сохранение палубы (id = waterway deck id)
     * Адаптирован для использования единого интерфейса UnifiedDatabase
     */
    public function saveDeck($waterwayDeckId, $name, $metaId = null, $metaName = null, $shipId = null)
    {
        return parent::saveDeck($waterwayDeckId, $name, [
            'ship_id' => $shipId,
            'meta_id' => $metaId,
            'meta_name' => $metaName
        ]);
    }

    /**
     * Batch сохранение палуб
     * Адаптирован для использования единого интерфейса UnifiedDatabase
     */
    public function saveDecksBatch($decks)
    {
        // Преобразуем формат данных для единого интерфейса
        $normalizedDecks = [];
        foreach ($decks as $deck) {
            $normalizedDecks[] = [
                'id' => $deck['id'],
                'name' => $deck['name'],
                'ship_id' => $deck['ship_id'] ?? null,
                'meta_id' => $deck['meta_id'] ?? null,
                'meta_name' => $deck['meta_name'] ?? null
            ];
        }
        
        parent::saveDecksBatch($normalizedDecks);
    }

    /**
     * Сохранение цены
     * Адаптирован для использования единого интерфейса UnifiedDatabase
     * Избыточные поля (cabin_category_name, cabin_category_desc, deck_name) сохраняются в extra_data
     */
    public function savePrice($cruiseId, $cabinCategoryId = null, $cabinCategoryName = null, $cabinCategoryDesc = null, $deckId = null, $deckName = null, $priceValue = null, $tariffName = null)
    {
        if (!$cabinCategoryId || !$priceValue) {
            throw new \Exception("cabin_category_id и price_value обязательны для цены");
        }
        
        // Сохраняем избыточные поля в extra_data для обратной совместимости
        $extraData = [];
        if (!empty($cabinCategoryName)) {
            $extraData['cabin_category_name'] = $cabinCategoryName;
        }
        if (!empty($cabinCategoryDesc)) {
            $extraData['cabin_category_desc'] = $cabinCategoryDesc;
        }
        if (!empty($deckName)) {
            $extraData['deck_name'] = $deckName;
        }
        
        return parent::savePrice($cruiseId, $cabinCategoryId, $priceValue, [
            'deck_id' => $deckId,
            'tariff_name' => $tariffName,
            'extra_data' => !empty($extraData) ? $extraData : null
        ]);
    }

    /**
     * Batch сохранение цен
     * Адаптирован для использования единого интерфейса UnifiedDatabase
     */
    public function savePricesBatch($prices)
    {
        // Преобразуем формат данных для единого интерфейса
        $normalizedPrices = [];
        foreach ($prices as $price) {
            if (empty($price['cabin_category_id']) || empty($price['price_value'])) {
                continue; // Пропускаем цены без обязательных полей
            }
            
            // Сохраняем избыточные поля в extra_data для обратной совместимости
            $extraData = [];
            if (!empty($price['cabin_category_name'])) {
                $extraData['cabin_category_name'] = $price['cabin_category_name'];
            }
            if (!empty($price['cabin_category_desc'])) {
                $extraData['cabin_category_desc'] = $price['cabin_category_desc'];
            }
            if (!empty($price['deck_name'])) {
                $extraData['deck_name'] = $price['deck_name'];
            }
            
            $normalizedPrices[] = [
                'cruise_id' => $price['cruise_id'],
                'cabin_category_id' => $price['cabin_category_id'],
                'price_value' => $price['price_value'],
                'deck_id' => $price['deck_id'] ?? null,
                'tariff_name' => $price['tariff_name'] ?? null,
                'extra_data' => !empty($extraData) ? $extraData : null
            ];
        }
        
        parent::savePricesBatch($normalizedPrices);
    }

    /**
     * Получение категории кают по Waterway ID
     */
    public function getCabinCategoryByWaterwayId($waterwayCategoryId)
    {
        $stmt = $this->getPdo()->prepare("SELECT * FROM cabin_categories WHERE id = ?");
        $stmt->execute([$waterwayCategoryId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        // Декодируем extra_data если есть
        if ($result && !empty($result['extra_data'])) {
            $result['extra_data'] = json_decode($result['extra_data'], true);
        }
        
        return $result ?: null;
    }

    /**
     * Получение палубы по Waterway ID
     */
    public function getDeckByWaterwayId($waterwayDeckId)
    {
        $stmt = $this->getPdo()->prepare("SELECT * FROM decks WHERE id = ?");
        $stmt->execute([$waterwayDeckId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        // Декодируем extra_data если есть
        if ($result && !empty($result['extra_data'])) {
            $result['extra_data'] = json_decode($result['extra_data'], true);
        }
        
        return $result ?: null;
    }
}

