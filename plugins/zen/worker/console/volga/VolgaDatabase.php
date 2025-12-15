<?php namespace Zen\Worker\Console\volga;

use PDO;
use Exception;
use Zen\Worker\Console\unified\UnifiedDatabase;
use Zen\Worker\Console\transfer\TransferConfig;

/**
 * VolgaDatabase - наследуется от UnifiedDatabase
 * Использует единую структуру SQLite для всех источников
 * Сохраняет специфичные таблицы cabins и waybills для Volga
 */
class VolgaDatabase extends UnifiedDatabase
{
    /**
     * Конструктор - передает путь к базе данных в родительский класс
     */
    public function __construct(bool $createIfMissing = false)
    {
        // Получаем путь к базе данных из конфигурации
        $dbPath = TransferConfig::getDbPath('volga');

        // Для фазы 1 (парсер) база может отсутствовать — UnifiedDatabase создаст её сам.
        // Для фазы 2 (transfer) лучше падать, чтобы не импортировать пустую базу по ошибке.
        if (!file_exists($dbPath) && !$createIfMissing) {
            throw new Exception("База данных volga_data.sqlite не найдена: {$dbPath}");
        }

        // Если разрешено создание — убеждаемся, что директория существует и доступна для записи
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

        // Проверка и исправление прав доступа (специфично для Volga)
        $this->ensureDatabasePermissions($dbPath);
        
        parent::__construct($dbPath);
        
        // Миграция: добавляем поля для обратной совместимости (если их нет)
        $this->migrateLegacyFields();
        
        // Создаем специфичные таблицы Volga (cabins, waybills)
        $this->createVolgaSpecificTables();
    }
    
    /**
     * Проверка и исправление прав доступа к базе данных
     * Сохранено из оригинального VolgaDatabase для обратной совместимости
     */
    private function ensureDatabasePermissions($dbPath)
    {
        $dir = dirname($dbPath);
        
        // Убеждаемся, что директория существует и доступна для записи
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        
        // Если файл существует, проверяем и исправляем права
        if (file_exists($dbPath)) {
            // Проверяем, доступен ли файл для записи
            if (!is_writable($dbPath)) {
                // Пытаемся исправить права
                @chmod($dbPath, 0664);
                
                // Если всё ещё не доступен для записи, пробуем через chown
                if (!is_writable($dbPath)) {
                    // Получаем текущего пользователя
                    if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
                        $currentUser = posix_getpwuid(posix_geteuid());
                        if ($currentUser && isset($currentUser['name'])) {
                            @chown($dbPath, $currentUser['name']);
                        }
                    }
                }
            }
        } else {
            // Если файла нет, убеждаемся, что директория доступна для записи
            if (!is_writable($dir)) {
                @chmod($dir, 0775);
            }
        }
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
        $this->addColumnIfNotExists('cruises', 'schedule_html', 'TEXT');
        $this->addColumnIfNotExists('cruises', 'description', 'TEXT');
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
     * Создание специфичных таблиц Volga (cabins, waybills)
     * Вызывается после parent::createTables()
     */
    private function createVolgaSpecificTables()
    {
        // Таблица кают (для связи class_id и deck_id)
        $this->getPdo()->exec("
            CREATE TABLE IF NOT EXISTS cabins (
                id INTEGER PRIMARY KEY,
                class_id INTEGER,
                deck_id INTEGER,
                ship_id INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (class_id) REFERENCES cabin_categories(id),
                FOREIGN KEY (deck_id) REFERENCES decks(id),
                FOREIGN KEY (ship_id) REFERENCES ships(id)
            )
        ");

        // Таблица путевых листов
        $this->getPdo()->exec("
            CREATE TABLE IF NOT EXISTS waybills (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                cruise_id INTEGER,
                town_name TEXT,
                town_id INTEGER,
                order_index INTEGER,
                bold INTEGER DEFAULT 0,
                excursion TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (cruise_id) REFERENCES cruises(id)
            )
        ");

        // Создаем индексы для специфичных таблиц
        $this->getPdo()->exec("CREATE INDEX IF NOT EXISTS idx_cabins_class_id ON cabins(class_id)");
        $this->getPdo()->exec("CREATE INDEX IF NOT EXISTS idx_waybills_cruise_id ON waybills(cruise_id)");
    }


    /**
     * Сохранение теплохода (id = volga_ship_id)
     * Адаптирован для единого интерфейса UnifiedDatabase
     */
    public function saveShip($volgaShipId, $name, $data = [])
    {
        // Если используется старый формат (2 параметра)
        if (empty($data) && func_num_args() == 2) {
            return parent::saveShip($volgaShipId, $name);
        }
        
        // Новый формат - вызываем родительский метод
        return parent::saveShip($volgaShipId, $name, $data);
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
                'name' => $ship['name']
            ];
        }
        
        // Используем родительский метод
        parent::saveShipsBatch($convertedShips);
    }

    /**
     * Получение теплохода по Volga ID
     */
    public function getShipByVolgaId($volgaShipId)
    {
        return parent::getShipBySourceId($volgaShipId);
    }

    /**
     * Сохранение палубы (id = volga_deck_id)
     * Адаптирован для единого интерфейса UnifiedDatabase
     */
    public function saveDeck($volgaDeckId, $name, $data = [])
    {
        // Если используется старый формат (2 параметра)
        if (empty($data) && func_num_args() == 2) {
            return parent::saveDeck($volgaDeckId, $name);
        }
        
        // Новый формат - вызываем родительский метод
        return parent::saveDeck($volgaDeckId, $name, $data);
    }

    /**
     * Batch сохранение палуб
     * Адаптирован для единого интерфейса UnifiedDatabase
     */
    public function saveDecksBatch($decks)
    {
        // Конвертируем в единый формат
        $convertedDecks = [];
        foreach ($decks as $deck) {
            $convertedDecks[] = [
                'id' => $deck['id'],
                'name' => $deck['name']
            ];
        }
        
        // Используем родительский метод
        parent::saveDecksBatch($convertedDecks);
    }

    /**
     * Сохранение категории кают (id = volga_class_id)
     * Адаптирован для единого интерфейса UnifiedDatabase
     */
    public function saveCabinCategory($volgaClassId, $name, $shipId = null, $data = [], $comment = null, $placesMainCount = null, $placesExtraCount = null, $deckId = null, $noFull = 0)
    {
        // Если используется старый формат (8 параметров: id, name, comment, placesMainCount, placesExtraCount, deckId, shipId, noFull)
        if (func_num_args() >= 8 && $shipId === null && empty($data)) {
            // Извлекаем параметры из старого формата
            $oldComment = func_get_arg(2);
            $oldPlacesMainCount = func_get_arg(3);
            $oldPlacesExtraCount = func_get_arg(4);
            $oldDeckId = func_get_arg(5);
            $oldShipId = func_get_arg(6);
            $oldNoFull = func_get_arg(7);
            
            if ($oldShipId === null) {
                throw new \Exception("ship_id обязателен для категории кают");
            }
            
            // Конвертируем параметры в единый формат
            $convertedData = [
                'description' => $oldComment,
                'places' => $oldPlacesMainCount,
                'places_extra' => $oldPlacesExtraCount,
                'deck_id' => $oldDeckId
            ];
            
            // Сохраняем no_full в extra_data
            if ($oldNoFull > 0) {
                $convertedData['extra_data'] = ['no_full' => $oldNoFull];
            }
            
            return parent::saveCabinCategory($volgaClassId, $name, $oldShipId, $convertedData);
        }
        
        // Новый формат - проверяем ship_id
        if ($shipId === null) {
            throw new \Exception("ship_id обязателен для категории кают");
        }
        
        // Если $data пустой, но есть дополнительные параметры
        if (empty($data) && $comment !== null) {
            $data = [
                'description' => $comment,
                'places' => $placesMainCount,
                'places_extra' => $placesExtraCount,
                'deck_id' => $deckId
            ];
            
            if ($noFull > 0) {
                $data['extra_data'] = ['no_full' => $noFull];
            }
        }
        
        return parent::saveCabinCategory($volgaClassId, $name, $shipId, $data);
    }

    /**
     * Batch сохранение категорий кают
     * Адаптирован для единого интерфейса UnifiedDatabase
     */
    public function saveCabinCategoriesBatch($categories)
    {
        if (empty($categories)) {
            return;
        }
        
        // Конвертируем в единый формат
        $convertedCategories = [];
        foreach ($categories as $category) {
            // Формируем extra_data
            $extraData = [];
            if (isset($category['no_full']) && $category['no_full'] > 0) {
                $extraData['no_full'] = $category['no_full'];
            }
            
            $convertedCategories[] = [
                'id' => $category['id'],
                'name' => $category['name'],
                'description' => $category['comment'] ?? null,
                'places' => $category['places_main_count'] ?? 1,
                'places_extra' => $category['places_extra_count'] ?? null,
                'deck_id' => $category['deck_id'] ?? null,
                'ship_id' => $category['ship_id'] ?? null,
                'extra_data' => !empty($extraData) ? $extraData : null
            ];
        }
        
        // Используем родительский метод
        parent::saveCabinCategoriesBatch($convertedCategories);
    }

    /**
     * Сохранение каюты (связь class_id и deck_id)
     * Специфичный метод для Volga - использует getPdo()
     */
    public function saveCabin($volgaCabinId, $classId, $deckId, $shipId)
    {
        $stmt = $this->getPdo()->prepare("
            INSERT OR REPLACE INTO cabins (id, class_id, deck_id, ship_id) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$volgaCabinId, $classId, $deckId, $shipId]);
    }

    /**
     * Batch сохранение кают
     * Специфичный метод для Volga - использует getPdo()
     */
    public function saveCabinsBatch($cabins)
    {
        if (empty($cabins)) {
            return;
        }
        
        $this->getPdo()->beginTransaction();
        
        $stmt = $this->getPdo()->prepare("
            INSERT OR REPLACE INTO cabins (id, class_id, deck_id, ship_id) 
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($cabins as $cabin) {
            $stmt->execute([
                $cabin['id'],
                $cabin['class_id'],
                $cabin['deck_id'],
                $cabin['ship_id']
            ]);
        }
        
        $this->getPdo()->commit();
    }

    /**
     * Сохранение круиза (id = volga_cruise_id, ship_id = volga_ship_id)
     * Адаптирован для единого интерфейса UnifiedDatabase
     * Поддерживает старую сигнатуру для обратной совместимости
     */
    public function saveCruise($idOrData, $shipId = null, $name = null, $dateStart = null, $dateEnd = null, $data = [])
    {
        // Если первый параметр - массив (старый формат)
        if (is_array($idOrData)) {
            $cruiseData = $idOrData;
            
            // Извлекаем основные данные
            $cruiseId = $cruiseData['volga_cruise_id'];
            $cruiseShipId = $cruiseData['volga_ship_id'];
            $cruiseName = $cruiseData['name'] ?? '';
            
            // Формируем date_start и date_end из begin_date/begin_time и end_date/end_time
            $cruiseDateStart = $cruiseData['date_start'] ?? null;
            $cruiseDateEnd = $cruiseData['date_end'] ?? null;
            
            // Если date_start/date_end не заданы, но есть begin_date/begin_time
            if (!$cruiseDateStart && isset($cruiseData['begin_date']) && isset($cruiseData['begin_time'])) {
                $cruiseDateStart = date('Y-m-d', strtotime($cruiseData['begin_date'])) . ' ' . $cruiseData['begin_time'];
            }
            if (!$cruiseDateEnd && isset($cruiseData['end_date']) && isset($cruiseData['end_time'])) {
                $cruiseDateEnd = date('Y-m-d', strtotime($cruiseData['end_date'])) . ' ' . $cruiseData['end_time'];
            }
            
            // Формируем extra_data из дополнительных полей
            $extraData = [];
            if (isset($cruiseData['begin_date'])) {
                $extraData['begin_date'] = $cruiseData['begin_date'];
            }
            if (isset($cruiseData['begin_time'])) {
                $extraData['begin_time'] = $cruiseData['begin_time'];
            }
            if (isset($cruiseData['end_date'])) {
                $extraData['end_date'] = $cruiseData['end_date'];
            }
            if (isset($cruiseData['end_time'])) {
                $extraData['end_time'] = $cruiseData['end_time'];
            }
            
            // Обрабатываем waybill_data
            $waybillData = $cruiseData['waybill_data'] ?? null;
            if (is_string($waybillData)) {
                // Если это JSON строка, декодируем
                $decoded = json_decode($waybillData, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $waybillData = $decoded;
                } else {
                    $waybillData = [];
                }
            }
            
            // Вызываем родительский метод с единым интерфейсом
            return parent::saveCruise(
                $cruiseId,
                $cruiseShipId,
                $cruiseName,
                $cruiseDateStart ?? '',
                $cruiseDateEnd ?? '',
                [
                    'route' => $cruiseData['route'] ?? null,
                    'waybill_data' => $waybillData,
                    'extra_data' => !empty($extraData) ? $extraData : null
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
            // Формируем date_start и date_end
            $dateStart = $cruise['date_start'] ?? null;
            $dateEnd = $cruise['date_end'] ?? null;
            
            // Если date_start/date_end не заданы, но есть begin_date/begin_time
            if (!$dateStart && isset($cruise['begin_date']) && isset($cruise['begin_time'])) {
                $dateStart = date('Y-m-d', strtotime($cruise['begin_date'])) . ' ' . $cruise['begin_time'];
            }
            if (!$dateEnd && isset($cruise['end_date']) && isset($cruise['end_time'])) {
                $dateEnd = date('Y-m-d', strtotime($cruise['end_date'])) . ' ' . $cruise['end_time'];
            }
            
            // Формируем extra_data из дополнительных полей
            $extraData = [];
            if (isset($cruise['begin_date'])) {
                $extraData['begin_date'] = $cruise['begin_date'];
            }
            if (isset($cruise['begin_time'])) {
                $extraData['begin_time'] = $cruise['begin_time'];
            }
            if (isset($cruise['end_date'])) {
                $extraData['end_date'] = $cruise['end_date'];
            }
            if (isset($cruise['end_time'])) {
                $extraData['end_time'] = $cruise['end_time'];
            }
            
            // Обрабатываем waybill_data
            $waybillData = $cruise['waybill_data'] ?? null;
            if (is_string($waybillData)) {
                // Если это JSON строка, декодируем
                $decoded = json_decode($waybillData, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $waybillData = $decoded;
                } else {
                    $waybillData = [];
                }
            }
            
            $convertedCruises[] = [
                'id' => $cruise['volga_cruise_id'],
                'ship_id' => $cruise['volga_ship_id'],
                'name' => $cruise['name'] ?? '',
                'date_start' => $dateStart ?? '',
                'date_end' => $dateEnd ?? '',
                'route' => $cruise['route'] ?? null,
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
     * Конвертирует price2Value → price_extra, nofull → nofull в $data
     */
    public function savePrice($cruiseId, $cabinCategoryId, $priceValue, $price2Value = null, $nofull = 0, $data = [])
    {
        // Если используется старый формат (5 параметров)
        if (func_num_args() <= 5 && empty($data)) {
            // Конвертируем price2Value → price_extra, nofull → nofull в $data
            $convertedData = [
                'price_extra' => $price2Value,
                'nofull' => $nofull
            ];
            
            return parent::savePrice($cruiseId, $cabinCategoryId, $priceValue, $convertedData);
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
            $convertedPrices[] = [
                'cruise_id' => $price['cruise_id'],
                'cabin_category_id' => $price['cabin_category_id'],
                'price_value' => $price['price_value'],
                'price_extra' => $price['price2_value'] ?? null,
                'nofull' => $price['nofull'] ?? 0
            ];
        }
        
        // Используем родительский метод
        parent::savePricesBatch($convertedPrices);
    }

    /**
     * Сохранение путевого листа
     * Специфичный метод для Volga - использует getPdo()
     */
    public function saveWaybill($cruiseId, $townName, $townId, $orderIndex, $bold = 0, $excursion = '')
    {
        $stmt = $this->getPdo()->prepare("
            INSERT INTO waybills 
            (cruise_id, town_name, town_id, order_index, bold, excursion) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$cruiseId, $townName, $townId, $orderIndex, $bold, $excursion]);
    }

    /**
     * Batch сохранение путевых листов
     * Специфичный метод для Volga - использует getPdo()
     */
    public function saveWaybillsBatch($waybills)
    {
        if (empty($waybills)) {
            return;
        }
        
        $this->getPdo()->beginTransaction();
        
        $stmt = $this->getPdo()->prepare("
            INSERT INTO waybills 
            (cruise_id, town_name, town_id, order_index, bold, excursion) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($waybills as $waybill) {
            $stmt->execute([
                $waybill['cruise_id'],
                $waybill['town_name'],
                $waybill['town_id'],
                $waybill['order_index'],
                $waybill['bold'] ?? 0,
                $waybill['excursion'] ?? ''
            ]);
        }
        
        $this->getPdo()->commit();
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
            // Добавляем volga_cruise_id и volga_ship_id для обратной совместимости
            $cruise['volga_cruise_id'] = $cruise['id'];
            $cruise['volga_ship_id'] = $cruise['ship_id'];
            
            // Извлекаем дополнительные поля из extra_data
            if (!empty($cruise['extra_data'])) {
                $extraData = is_string($cruise['extra_data']) 
                    ? json_decode($cruise['extra_data'], true) 
                    : $cruise['extra_data'];
                
                if (is_array($extraData)) {
                    if (isset($extraData['begin_date'])) {
                        $cruise['begin_date'] = $extraData['begin_date'];
                    }
                    if (isset($extraData['begin_time'])) {
                        $cruise['begin_time'] = $extraData['begin_time'];
                    }
                    if (isset($extraData['end_date'])) {
                        $cruise['end_date'] = $extraData['end_date'];
                    }
                    if (isset($extraData['end_time'])) {
                        $cruise['end_time'] = $extraData['end_time'];
                    }
                }
            }
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
        
        if (!$cruise) {
            return null;
        }
        
        // Добавляем volga_cruise_id и volga_ship_id для обратной совместимости
        $cruise['volga_cruise_id'] = $cruise['id'];
        $cruise['volga_ship_id'] = $cruise['ship_id'];
        
        // Извлекаем дополнительные поля из extra_data
        if (!empty($cruise['extra_data'])) {
            $extraData = is_string($cruise['extra_data']) 
                ? json_decode($cruise['extra_data'], true) 
                : $cruise['extra_data'];
            
            if (is_array($extraData)) {
                if (isset($extraData['begin_date'])) {
                    $cruise['begin_date'] = $extraData['begin_date'];
                }
                if (isset($extraData['begin_time'])) {
                    $cruise['begin_time'] = $extraData['begin_time'];
                }
                if (isset($extraData['end_date'])) {
                    $cruise['end_date'] = $extraData['end_date'];
                }
                if (isset($extraData['end_time'])) {
                    $cruise['end_time'] = $extraData['end_time'];
                }
            }
        }
        
        return $cruise;
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
            // Конвертируем price_extra → price2_value
            if (isset($price['price_extra'])) {
                $price['price2_value'] = $price['price_extra'];
            }
            
            // Добавляем поля категорий для обратной совместимости
            if (isset($price['category_name'])) {
                // Получаем дополнительные данные категории из единой структуры
                $stmt = $this->getPdo()->prepare("
                    SELECT description, places, places_extra, extra_data 
                    FROM cabin_categories 
                    WHERE id = ?
                ");
                $stmt->execute([$price['cabin_category_id']]);
                $category = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($category) {
                    // Конвертируем единую структуру в старый формат для обратной совместимости
                    $price['comment'] = $category['description'] ?? null;
                    $price['places_main_count'] = $category['places'] ?? null;
                    $price['places_extra_count'] = $category['places_extra'] ?? null;
                }
            }
        }
        
        return $prices;
    }

    /**
     * Получение путевого листа для круиза
     * Использует таблицу waybills (приоритет) или JSON из waybill_data
     */
    public function getCruiseWaybill($cruiseId)
    {
        // Сначала пытаемся получить из таблицы waybills
        $stmt = $this->getPdo()->prepare("
            SELECT * FROM waybills 
            WHERE cruise_id = ? 
            ORDER BY order_index
        ");
        $stmt->execute([$cruiseId]);
        $waybills = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Если есть данные в таблице, возвращаем их
        if (!empty($waybills)) {
            return $waybills;
        }
        
        // Если нет, пытаемся получить из JSON waybill_data
        $cruise = parent::getCruiseById($cruiseId);
        if ($cruise && !empty($cruise['waybill_data'])) {
            $waybillData = is_string($cruise['waybill_data']) 
                ? json_decode($cruise['waybill_data'], true) 
                : $cruise['waybill_data'];
            
            if (is_array($waybillData) && !empty($waybillData)) {
                // Конвертируем JSON формат в формат таблицы waybills
                $result = [];
                foreach ($waybillData as $index => $point) {
                    $result[] = [
                        'id' => null,
                        'cruise_id' => $cruiseId,
                        'town_name' => $point['town_name'] ?? '',
                        'town_id' => $point['town'] ?? 0,
                        'order_index' => $index,
                        'bold' => $point['bold'] ?? 0,
                        'excursion' => $point['excursion'] ?? '',
                        'created_at' => null
                    ];
                }
                return $result;
            }
        }
        
        return [];
    }

    /**
     * Получение статистики
     * Использует родительский метод и добавляет специфичные для Volga данные
     */
    public function getStats()
    {
        $stats = parent::getStats();
        
        // Добавляем специфичные для Volga данные
        $stmt = $this->getPdo()->query("SELECT COUNT(*) as count FROM cabins");
        $stats['cabins'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $this->getPdo()->query("SELECT COUNT(*) as count FROM waybills");
        $stats['waybills'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        return $stats;
    }

    /**
     * Очистка всех данных
     * Использует родительский метод и очищает специфичные таблицы Volga
     */
    public function clearAll()
    {
        // Очищаем специфичные таблицы Volga
        $this->getPdo()->exec("DELETE FROM waybills");
        $this->getPdo()->exec("DELETE FROM cabins");
        
        // Используем родительский метод для остальных таблиц
        parent::clearAll();
    }

    /**
     * Очистка круизов без цен (вызывается в конце фазы 1)
     * Использует родительский метод и очищает специфичные таблицы Volga
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
                    // Удаляем путевые листы (специфично для Volga)
                    $stmt = $this->getPdo()->prepare("DELETE FROM waybills WHERE cruise_id = ?");
                    $stmt->execute([$cruiseId]);
                    
                    // Удаляем сам круиз
                    $stmt = $this->getPdo()->prepare("DELETE FROM cruises WHERE id = ?");
                    $stmt->execute([$cruiseId]);
                    
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

