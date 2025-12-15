<?php namespace Zen\Worker\Console\germes;

use PDO;
use Exception;
use Zen\Worker\Console\unified\UnifiedDatabase;
use Zen\Worker\Console\transfer\TransferConfig;

/**
 * GermesDatabase - наследуется от UnifiedDatabase
 * Использует единую структуру SQLite для всех источников
 * Сохраняет таблицу cabins для обратной совместимости
 */
class GermesDatabase extends UnifiedDatabase
{
    /**
     * Маппинг AUTOINCREMENT deck_id → hash имени (для обратной совместимости)
     * @var array
     */
    private $deckIdMapping = [];

    /**
     * Конструктор - передает путь к базе данных в родительский класс
     */
    public function __construct(bool $createIfMissing = false)
    {
        // Получаем путь к базе данных из конфигурации
        $dbPath = TransferConfig::getDbPath('germes');
        if (!file_exists($dbPath) && !$createIfMissing) {
            throw new Exception("База данных germes_data.sqlite не найдена: {$dbPath}");
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
        
        // Создаем таблицу cabins для обратной совместимости
        $this->createCabinsTable();
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
        $this->addColumnIfNotExists('cruises', 'schedule_html', 'TEXT');
        $this->addColumnIfNotExists('cruises', 'extra_data', 'TEXT');
        
        // Миграция таблицы prices
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
     * Создание таблицы cabins для обратной совместимости
     */
    private function createCabinsTable()
    {
        if (!$this->tableExists('cabins')) {
            $this->getPdo()->exec("
                CREATE TABLE IF NOT EXISTS cabins (
                    id INTEGER PRIMARY KEY,
                    cabin_category_id INTEGER,
                    number INTEGER,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
            $this->getPdo()->exec("CREATE INDEX IF NOT EXISTS idx_cabins_cabin_category_id ON cabins(cabin_category_id)");
            $this->getPdo()->exec("CREATE INDEX IF NOT EXISTS idx_cabins_number ON cabins(number)");
        }
    }

    /**
     * Сохранение теплохода (id = germes_ship_id)
     * Адаптирован для единого интерфейса UnifiedDatabase
     */
    public function saveShip($germesShipId, $name, $data = [])
    {
        return parent::saveShip($germesShipId, $name, $data);
    }

    /**
     * Batch сохранение теплоходов
     * Адаптирован для единого интерфейса UnifiedDatabase
     */
    public function saveShipsBatch($ships)
    {
        // Конвертируем в единый формат и используем родительский метод
        $convertedShips = [];
        foreach ($ships as $ship) {
            $convertedShips[] = [
                'id' => $ship['id'],
                'name' => $ship['name']
            ];
        }
        parent::saveShipsBatch($convertedShips);
    }

    /**
     * Получение теплохода по Germes ID
     */
    public function getShipByGermesId($germesShipId)
    {
        return parent::getShipBySourceId($germesShipId);
    }

    /**
     * Сохранение палубы
     * Адаптирован для единого интерфейса UnifiedDatabase
     * Использует hash имени как ID (вместо AUTOINCREMENT)
     * Сохраняет маппинг для обратной совместимости
     */
    public function saveDeck($id, $name = null, $data = [])
    {
        // Если используется старый формат (только $name - один аргумент)
        if (func_num_args() == 1 && is_string($id)) {
            // Используем числовой hash имени как ID (для единой структуры)
            // Берем первые 8 символов md5 hash и конвертируем в число
            $deckName = (string)$id;
            $deckId = abs(hexdec(substr(md5($deckName), 0, 8)));
            $deckData = [];
        } else {
            // Новый формат - проверяем типы
            if (!is_numeric($id) && !is_int($id)) {
                throw new \Exception("deck_id должен быть числом, получен: " . gettype($id));
            }
            if (!is_string($name) && !is_numeric($name)) {
                throw new \Exception("deck_name должен быть строкой, получен: " . gettype($name));
            }
            $deckId = (int)$id;
            $deckName = (string)$name;
            $deckData = is_array($data) ? $data : [];
        }
        
        // Сохраняем палубу через родительский метод
        parent::saveDeck($deckId, $deckName, $deckData);
        
        // Сохраняем маппинг для обратной совместимости
        $this->deckIdMapping[$deckName] = $deckId;
        
        return $deckId;
    }

    /**
     * Получение ID палубы из имени (hash)
     */
    private function getDeckIdFromName($name)
    {
        // Используем hash имени как ID для единой структуры
        return abs(crc32($name));
    }

    /**
     * Получение палубы по названию
     * Адаптирован для единой структуры
     */
    public function getDeckByName($name)
    {
        $deckId = $this->getDeckIdFromName($name);
        
        // Проверяем, существует ли палуба с таким ID
        $stmt = $this->getPdo()->prepare("SELECT id FROM decks WHERE id = ? OR name = ?");
        $stmt->execute([$deckId, $name]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return (int)$result['id'];
        }
        
        return null;
    }
    
    /**
     * Сохранение палубы (старая сигнатура для обратной совместимости)
     */
    public function saveDeckOld($name)
    {
        return $this->saveDeck($name);
    }

    /**
     * Сохранение категории кают (id = germes_class_id)
     * Адаптирован для единого интерфейса UnifiedDatabase
     * Поддерживает старую сигнатуру для обратной совместимости
     */
    public function saveCabinCategory($germesClassId, $name, $shipId = null, $data = [], $description = null, $deckId = null)
    {
        // Если используется старый формат (5 параметров: id, name, description, shipId, deckId)
        if (func_num_args() >= 5 && $shipId === null && empty($data)) {
            // Извлекаем параметры из старого формата
            $oldDescription = func_get_arg(2);
            $oldShipId = func_get_arg(3);
            $oldDeckId = func_get_arg(4);
            
            if ($oldShipId === null) {
                throw new \Exception("ship_id обязателен для категории кают");
            }
            
            // Конвертируем deckId если это имя палубы (для обратной совместимости)
            if (is_string($oldDeckId)) {
                $oldDeckId = $this->getDeckIdFromName($oldDeckId);
            }
            
            return parent::saveCabinCategory($germesClassId, $name, $oldShipId, [
                'description' => $oldDescription,
                'deck_id' => $oldDeckId,
                'places' => 1 // По умолчанию для Germes
            ]);
        }
        
        // Новый формат - проверяем ship_id
        if ($shipId === null) {
            throw new \Exception("ship_id обязателен для категории кают");
        }
        
        // Если $data пустой, но есть дополнительные параметры
        if (empty($data) && $description !== null) {
            // Конвертируем deckId если это имя палубы
            if (is_string($deckId)) {
                $deckId = $this->getDeckIdFromName($deckId);
            }
            
            $data = [
                'description' => $description,
                'deck_id' => $deckId,
                'places' => 1 // По умолчанию для Germes
            ];
        }
        
        return parent::saveCabinCategory($germesClassId, $name, $shipId, $data);
    }

    /**
     * Batch сохранение категорий кают
     * Адаптирован для единого интерфейса UnifiedDatabase
     */
    public function saveCabinCategoriesBatch($categories)
    {
        // Конвертируем в единый формат
        $convertedCategories = [];
        foreach ($categories as $category) {
            $deckId = $category['deck_id'] ?? null;
            // Если deck_id это строка (имя палубы), конвертируем в ID
            if (is_string($deckId)) {
                $deckId = $this->getDeckIdFromName($deckId);
            }
            
            $convertedCategories[] = [
                'id' => $category['id'],
                'name' => $category['name'],
                'ship_id' => $category['ship_id'] ?? null,
                'description' => $category['description'] ?? null,
                'deck_id' => $deckId,
                'places' => 1 // По умолчанию для Germes
            ];
        }
        
        // Используем родительский метод
        parent::saveCabinCategoriesBatch($convertedCategories);
    }

    /**
     * Сохранение каюты (pivot)
     * Сохраняется в таблицу cabins для обратной совместимости
     */
    public function saveCabin($germesCabinId, $cabinCategoryId, $cabinNumber = null)
    {
        // Убеждаемся, что таблица cabins существует
        $this->createCabinsTable();
        
        $stmt = $this->getPdo()->prepare("
            INSERT OR REPLACE INTO cabins (id, cabin_category_id, number) 
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$germesCabinId, $cabinCategoryId, $cabinNumber]);
    }

    /**
     * Batch сохранение кают
     * Сохраняется в таблицу cabins для обратной совместимости
     */
    public function saveCabinsBatch($cabins)
    {
        if (empty($cabins)) {
            return;
        }
        
        // Убеждаемся, что таблица cabins существует
        $this->createCabinsTable();
        
        $this->getPdo()->beginTransaction();
        
        try {
            $stmt = $this->getPdo()->prepare("
                INSERT OR REPLACE INTO cabins (id, cabin_category_id, number) 
                VALUES (?, ?, ?)
            ");
            
            foreach ($cabins as $cabin) {
                $stmt->execute([
                    $cabin['id'],
                    $cabin['cabin_category_id'],
                    $cabin['number'] ?? null
                ]);
            }
            
            $this->getPdo()->commit();
        } catch (\Exception $e) {
            $this->getPdo()->rollBack();
            throw $e;
        }
    }

    /**
     * Сохранение круиза (id = germes_cruise_id, ship_id = germes_ship_id)
     * Адаптирован для единого интерфейса UnifiedDatabase
     * Поддерживает старую сигнатуру для обратной совместимости
     */
    public function saveCruise($idOrData, $shipId = null, $name = null, $dateStart = null, $dateEnd = null, $data = [])
    {
        // Если первый параметр - массив (старый формат)
        if (is_array($idOrData)) {
            $cruiseData = $idOrData;
            
            // Получаем waybill_data (может быть JSON строка или массив)
            $waybillData = $cruiseData['waybill_data'] ?? '[]';
            if (is_string($waybillData)) {
                $decoded = json_decode($waybillData, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $waybillData = $decoded;
                } else {
                    $waybillData = [];
                }
            }
            
            // Вызываем родительский метод с единым интерфейсом
            return parent::saveCruise(
                $cruiseData['germes_cruise_id'],
                $cruiseData['germes_ship_id'],
                $cruiseData['name'] ?? '',
                $cruiseData['date_start'] ?? '',
                $cruiseData['date_end'] ?? '',
                [
                    'route' => $cruiseData['route'] ?? null,
                    'waybill_data' => $waybillData
                ]
            );
        }
        
        // Новый формат - вызываем родительский метод напрямую
        return parent::saveCruise($idOrData, $shipId, $name, $dateStart, $dateEnd, $data);
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
                'id' => $cruise['germes_cruise_id'],
                'ship_id' => $cruise['germes_ship_id'],
                'name' => $cruise['name'] ?? '',
                'date_start' => $cruise['date_start'] ?? '',
                'date_end' => $cruise['date_end'] ?? '',
                'route' => $cruise['route'] ?? null,
                'waybill_data' => $waybillData
            ];
        }
        
        // Вызываем родительский метод с конвертированными данными
        parent::saveCruisesBatch($convertedCruises);
    }

    /**
     * Сохранение цены
     * Адаптирован для единого интерфейса UnifiedDatabase
     */
    public function savePrice($cruiseId, $cabinCategoryId, $priceValue, $data = [])
    {
        // Germes использует только price_value (price_extra = null)
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
            $convertedPrices[] = [
                'cruise_id' => $price['cruise_id'],
                'cabin_category_id' => $price['cabin_category_id'],
                'price_value' => $price['price_value'],
                'price_extra' => null // Germes не использует price_extra
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
            // Добавляем germes_cruise_id и germes_ship_id для обратной совместимости
            $cruise['germes_cruise_id'] = $cruise['id'];
            $cruise['germes_ship_id'] = $cruise['ship_id'];
        }
        
        return $cruises;
    }

    /**
     * Получение круиза по ID
     * Адаптирован для единой структуры
     */
    public function getCruiseById($cruiseId)
    {
        $cruise = parent::getCruiseById($cruiseId);
        
        if ($cruise) {
            // Добавляем germes_cruise_id и germes_ship_id для обратной совместимости
            $cruise['germes_cruise_id'] = $cruise['id'];
            $cruise['germes_ship_id'] = $cruise['ship_id'];
        }
        
        return $cruise;
    }

    /**
     * Получение цен для круиза по ID
     * Адаптирован для единой структуры
     */
    public function getPricesByCruiseId($cruiseId)
    {
        return parent::getPricesByCruiseId($cruiseId);
    }
    
    /**
     * Получение статистики
     * Использует родительский метод и добавляет статистику по cabins
     */
    public function getStats()
    {
        $stats = parent::getStats();
        
        // Добавляем статистику по cabins (для обратной совместимости)
        if ($this->tableExists('cabins')) {
            $stmt = $this->getPdo()->query("SELECT COUNT(*) as count FROM cabins");
            $stats['cabins'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        } else {
            $stats['cabins'] = 0;
        }
        
        return $stats;
    }

    /**
     * Очистка всех данных
     * Включает очистку таблицы cabins
     */
    public function clearAll()
    {
        // Очищаем таблицу cabins если она существует
        if ($this->tableExists('cabins')) {
            $this->getPdo()->exec("DELETE FROM cabins");
        }
        
        // Вызываем родительский метод для очистки основных таблиц
        parent::clearAll();
    }

    /**
     * Обновление ship_id в cabin_categories на основе данных из круизов и цен
     * Используется для восстановления связей после обработки круизов
     */
    public function updateCabinCategoriesShipId()
    {
        try {
            $this->getPdo()->beginTransaction();
            
            // Получаем маппинг category_id -> ship_id из цен и круизов
            $stmt = $this->getPdo()->query("
                SELECT DISTINCT p.cabin_category_id, c.ship_id
                FROM prices p
                INNER JOIN cruises c ON p.cruise_id = c.id
                WHERE c.ship_id IS NOT NULL
            ");
            
            $mapping = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $categoryId = (int)$row['cabin_category_id'];
                $shipId = (int)$row['ship_id'];
                
                // Если категория уже имеет ship_id, не перезаписываем (берем первый найденный)
                if (!isset($mapping[$categoryId])) {
                    $mapping[$categoryId] = $shipId;
                }
            }
            
            // Обновляем ship_id в cabin_categories
            $updateStmt = $this->getPdo()->prepare("
                UPDATE cabin_categories 
                SET ship_id = ? 
                WHERE id = ? AND (ship_id IS NULL OR ship_id = 0)
            ");
            
            $updated = 0;
            foreach ($mapping as $categoryId => $shipId) {
                if ($shipId > 0) {
                    $updateStmt->execute([$shipId, $categoryId]);
                    $updated += $updateStmt->rowCount();
                }
            }
            
            $this->getPdo()->commit();
            
            return $updated;
        } catch (\Exception $e) {
            $this->getPdo()->rollBack();
            throw new \Exception("Ошибка при обновлении ship_id в cabin_categories: " . $e->getMessage());
        }
    }

    /**
     * Обновление deck_id в cabin_categories на основе данных из описаний
     */
    public function updateCabinCategoriesDeckId($categoryToDeckMap)
    {
        if (empty($categoryToDeckMap)) {
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
            foreach ($categoryToDeckMap as $categoryId => $deckId) {
                // Если deckId это строка (имя палубы), конвертируем в ID
                if (is_string($deckId)) {
                    $deckId = $this->getDeckIdFromName($deckId);
                }
                
                if ($deckId !== null && $deckId > 0) {
                    $stmt->execute([$deckId, $categoryId]);
                    $updated += $stmt->rowCount();
                }
            }
            
            $this->getPdo()->commit();
            
            return $updated;
        } catch (\Exception $e) {
            $this->getPdo()->rollBack();
            throw new \Exception("Ошибка при обновлении deck_id в cabin_categories: " . $e->getMessage());
        }
    }

    /**
     * Удаление категорий кают без привязки к теплоходу
     */
    public function deleteCabinCategoriesWithoutShip()
    {
        try {
            // Сначала удаляем цены для этих категорий
            $this->getPdo()->exec("
                DELETE FROM prices 
                WHERE cabin_category_id IN (
                    SELECT id FROM cabin_categories WHERE ship_id IS NULL
                )
            ");
            
            // Затем удаляем сами категории
            $stmt = $this->getPdo()->query("
                SELECT COUNT(*) as count FROM cabin_categories WHERE ship_id IS NULL
            ");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            $this->getPdo()->exec("
                DELETE FROM cabin_categories WHERE ship_id IS NULL
            ");
            
            return (int)$count;
        } catch (\Exception $e) {
            throw new \Exception("Ошибка при удалении категорий без теплохода: " . $e->getMessage());
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

