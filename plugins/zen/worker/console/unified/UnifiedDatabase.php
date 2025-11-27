<?php namespace Zen\Worker\Console\unified;

use PDO;
use Exception;

/**
 * Базовый класс для единой структуры SQLite баз данных
 * Используется всеми парсерами (Gama, Germes, Infoflot, Volga, Waterway) на фазе 1
 */
abstract class UnifiedDatabase
{
    protected $pdo;
    protected $dbPath;

    /**
     * Конструктор
     * @param string $dbPath Путь к файлу SQLite базы данных
     */
    public function __construct($dbPath)
    {
        $this->dbPath = $dbPath;
        $this->initDatabase();
    }

    /**
     * Инициализация базы данных
     */
    private function initDatabase()
    {
        try {
            $this->pdo = new PDO("sqlite:" . $this->dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Включаем foreign keys для проверки целостности
            $this->pdo->exec("PRAGMA foreign_keys = ON");
            $this->createTables();
            
            // Устанавливаем права доступа для базы данных
            if (file_exists($this->dbPath)) {
                chmod($this->dbPath, 0664);
            }
        } catch (Exception $e) {
            throw new Exception("Ошибка подключения к SQLite: " . $e->getMessage());
        }
    }

    /**
     * Создание единых таблиц
     */
    protected function createTables()
    {
        // Таблица теплоходов
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS ships (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL,
                description TEXT,
                type TEXT,
                operator_name TEXT,
                extra_data TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Таблица палуб
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS decks (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL,
                ship_id INTEGER,
                position INTEGER,
                meta_id INTEGER,
                meta_name TEXT,
                extra_data TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (ship_id) REFERENCES ships(id)
            )
        ");

        // Таблица категорий кают
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS cabin_categories (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL,
                description TEXT,
                places INTEGER DEFAULT 1,
                places_extra INTEGER,
                deck_id INTEGER,
                ship_id INTEGER NOT NULL,
                meta_id INTEGER,
                meta_name TEXT,
                extra_data TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (deck_id) REFERENCES decks(id),
                FOREIGN KEY (ship_id) REFERENCES ships(id)
            )
        ");

        // Таблица круизов
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS cruises (
                id INTEGER PRIMARY KEY,
                ship_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                route TEXT,
                date_start DATETIME NOT NULL,
                date_end DATETIME NOT NULL,
                days INTEGER,
                nights INTEGER,
                waybill_data TEXT NOT NULL,
                schedule_html TEXT,
                description TEXT,
                extra_data TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (ship_id) REFERENCES ships(id)
            )
        ");

        // Таблица цен
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS prices (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                cruise_id INTEGER NOT NULL,
                cabin_category_id INTEGER NOT NULL,
                deck_id INTEGER,
                price_value INTEGER NOT NULL,
                price_extra INTEGER,
                places_qnt INTEGER DEFAULT 1,
                nofull INTEGER DEFAULT 0,
                tariff_name TEXT,
                extra_data TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (cruise_id) REFERENCES cruises(id),
                FOREIGN KEY (cabin_category_id) REFERENCES cabin_categories(id),
                FOREIGN KEY (deck_id) REFERENCES decks(id)
            )
        ");

        // Создаем индексы для быстрого поиска
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_ships_name ON ships(name)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_decks_ship_id ON decks(ship_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_decks_name ON decks(name)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_cabin_categories_ship_id ON cabin_categories(ship_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_cabin_categories_deck_id ON cabin_categories(deck_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_cruises_ship_id ON cruises(ship_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_cruises_date_start ON cruises(date_start)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_cruises_date_end ON cruises(date_end)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_prices_cruise_id ON prices(cruise_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_prices_cabin_category_id ON prices(cabin_category_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_prices_deck_id ON prices(deck_id)");
    }

    /**
     * Сохранение теплохода
     * @param int $id ID теплохода из источника
     * @param string $name Название теплохода
     * @param array $data Дополнительные данные (description, type, operator_name, extra_data)
     * @return bool
     */
    public function saveShip($id, $name, $data = [])
    {
        $extraData = !empty($data['extra_data']) ? json_encode($data['extra_data'], JSON_UNESCAPED_UNICODE) : null;
        
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO ships (
                id, name, description, type, operator_name, extra_data, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        
        return $stmt->execute([
            $id,
            $name,
            $data['description'] ?? null,
            $data['type'] ?? null,
            $data['operator_name'] ?? null,
            $extraData
        ]);
    }

    /**
     * Сохранение палубы
     * @param int $id ID палубы из источника
     * @param string $name Название палубы
     * @param array $data Дополнительные данные (ship_id, position, meta_id, meta_name, extra_data)
     * @return bool
     */
    public function saveDeck($id, $name, $data = [])
    {
        $extraData = !empty($data['extra_data']) ? json_encode($data['extra_data'], JSON_UNESCAPED_UNICODE) : null;
        
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO decks (
                id, name, ship_id, position, meta_id, meta_name, extra_data
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $id,
            $name,
            $data['ship_id'] ?? null,
            $data['position'] ?? null,
            $data['meta_id'] ?? null,
            $data['meta_name'] ?? null,
            $extraData
        ]);
    }

    /**
     * Сохранение категории кают
     * @param int $id ID категории из источника
     * @param string $name Название категории
     * @param int $shipId ID теплохода
     * @param array $data Дополнительные данные (description, places, places_extra, deck_id, meta_id, meta_name, extra_data)
     * @return bool
     */
    public function saveCabinCategory($id, $name, $shipId, $data = [])
    {
        $extraData = !empty($data['extra_data']) ? json_encode($data['extra_data'], JSON_UNESCAPED_UNICODE) : null;
        
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO cabin_categories (
                id, name, description, places, places_extra, deck_id, ship_id, meta_id, meta_name, extra_data
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $id,
            $name,
            $data['description'] ?? null,
            $data['places'] ?? 1,
            $data['places_extra'] ?? null,
            $data['deck_id'] ?? null,
            $shipId,
            $data['meta_id'] ?? null,
            $data['meta_name'] ?? null,
            $extraData
        ]);
    }

    /**
     * Сохранение круиза
     * @param int $id ID круиза из источника
     * @param int $shipId ID теплохода
     * @param string $name Название круиза
     * @param string $dateStart Дата начала (DATETIME)
     * @param string $dateEnd Дата окончания (DATETIME)
     * @param array $data Дополнительные данные (route, days, nights, waybill_data, schedule_html, description, extra_data)
     * @return bool
     */
    public function saveCruise($id, $shipId, $name, $dateStart, $dateEnd, $data = [])
    {
        // waybill_data должен быть в JSON формате
        $waybillData = $data['waybill_data'] ?? '[]';
        if (is_array($waybillData)) {
            $waybillData = json_encode($waybillData, JSON_UNESCAPED_UNICODE);
        }
        
        $extraData = !empty($data['extra_data']) ? json_encode($data['extra_data'], JSON_UNESCAPED_UNICODE) : null;
        
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO cruises (
                id, ship_id, name, route, date_start, date_end, days, nights,
                waybill_data, schedule_html, description, extra_data, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        
        return $stmt->execute([
            $id,
            $shipId,
            $name,
            $data['route'] ?? null,
            $dateStart,
            $dateEnd,
            $data['days'] ?? null,
            $data['nights'] ?? null,
            $waybillData,
            $data['schedule_html'] ?? null,
            $data['description'] ?? null,
            $extraData
        ]);
    }

    /**
     * Сохранение цены
     * @param int $cruiseId ID круиза
     * @param int $cabinCategoryId ID категории кают
     * @param int $priceValue Основная цена
     * @param array $data Дополнительные данные (deck_id, price_extra, places_qnt, nofull, tariff_name, extra_data)
     * @return bool
     */
    public function savePrice($cruiseId, $cabinCategoryId, $priceValue, $data = [])
    {
        $extraData = !empty($data['extra_data']) ? json_encode($data['extra_data'], JSON_UNESCAPED_UNICODE) : null;
        
        $stmt = $this->pdo->prepare("
            INSERT INTO prices (
                cruise_id, cabin_category_id, deck_id, price_value, price_extra,
                places_qnt, nofull, tariff_name, extra_data
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $cruiseId,
            $cabinCategoryId,
            $data['deck_id'] ?? null,
            $priceValue,
            $data['price_extra'] ?? null,
            $data['places_qnt'] ?? 1,
            $data['nofull'] ?? 0,
            $data['tariff_name'] ?? null,
            $extraData
        ]);
    }

    /**
     * Получение всех круизов
     * @return array
     */
    public function getAllCruises()
    {
        $stmt = $this->pdo->prepare("
            SELECT c.*, s.name as ship_name
            FROM cruises c
            LEFT JOIN ships s ON c.ship_id = s.id
            ORDER BY c.date_start
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Получение цен для круиза
     * @param int $cruiseId ID круиза
     * @return array
     */
    public function getPricesByCruiseId($cruiseId)
    {
        $stmt = $this->pdo->prepare("
            SELECT p.*, 
                   cc.name as cabin_category_name, 
                   cc.places as cabin_category_places,
                   d.name as deck_name
            FROM prices p
            LEFT JOIN cabin_categories cc ON p.cabin_category_id = cc.id
            LEFT JOIN decks d ON p.deck_id = d.id
            WHERE p.cruise_id = ?
            ORDER BY p.price_value
        ");
        $stmt->execute([$cruiseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Получение теплохода по ID источника
     * @param int $shipId ID теплохода из источника
     * @return array|null
     */
    public function getShipBySourceId($shipId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM ships WHERE id = ?");
        $stmt->execute([$shipId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Декодируем extra_data если есть
        if ($result && !empty($result['extra_data'])) {
            $result['extra_data'] = json_decode($result['extra_data'], true);
        }
        
        return $result ?: null;
    }

    /**
     * Получение PDO объекта
     * @return PDO
     */
    public function getPdo()
    {
        return $this->pdo;
    }

    /**
     * Проверка существования таблицы
     * @param string $tableName Название таблицы
     * @return bool
     */
    public function tableExists($tableName)
    {
        $stmt = $this->pdo->prepare("
            SELECT name FROM sqlite_master 
            WHERE type='table' AND name=?
        ");
        $stmt->execute([$tableName]);
        return $stmt->fetch() !== false;
    }

    /**
     * Получение пути к базе данных
     * @return string
     */
    public function getDbPath()
    {
        return $this->dbPath;
    }

    /**
     * Batch сохранение теплоходов
     * @param array $ships Массив теплоходов, каждый элемент: ['id' => int, 'name' => string, ...]
     * @return void
     */
    public function saveShipsBatch($ships)
    {
        if (empty($ships)) {
            return;
        }
        
        $this->pdo->beginTransaction();
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT OR REPLACE INTO ships (
                    id, name, description, type, operator_name, extra_data, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");
            
            foreach ($ships as $ship) {
                $extraData = !empty($ship['extra_data']) ? json_encode($ship['extra_data'], JSON_UNESCAPED_UNICODE) : null;
                
                $stmt->execute([
                    $ship['id'],
                    $ship['name'],
                    $ship['description'] ?? null,
                    $ship['type'] ?? null,
                    $ship['operator_name'] ?? null,
                    $extraData
                ]);
            }
            
            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Batch сохранение палуб
     * @param array $decks Массив палуб, каждый элемент: ['id' => int, 'name' => string, ...]
     * @return void
     */
    public function saveDecksBatch($decks)
    {
        if (empty($decks)) {
            return;
        }
        
        $this->pdo->beginTransaction();
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT OR REPLACE INTO decks (
                    id, name, ship_id, position, meta_id, meta_name, extra_data
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($decks as $deck) {
                $extraData = !empty($deck['extra_data']) ? json_encode($deck['extra_data'], JSON_UNESCAPED_UNICODE) : null;
                
                $stmt->execute([
                    $deck['id'],
                    $deck['name'],
                    $deck['ship_id'] ?? null,
                    $deck['position'] ?? null,
                    $deck['meta_id'] ?? null,
                    $deck['meta_name'] ?? null,
                    $extraData
                ]);
            }
            
            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Batch сохранение категорий кают
     * @param array $categories Массив категорий, каждый элемент: ['id' => int, 'name' => string, 'ship_id' => int, ...]
     * @return void
     */
    public function saveCabinCategoriesBatch($categories)
    {
        if (empty($categories)) {
            return;
        }
        
        $this->pdo->beginTransaction();
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT OR REPLACE INTO cabin_categories (
                    id, name, description, places, places_extra, deck_id, ship_id, meta_id, meta_name, extra_data
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($categories as $category) {
                $extraData = !empty($category['extra_data']) ? json_encode($category['extra_data'], JSON_UNESCAPED_UNICODE) : null;
                
                $stmt->execute([
                    $category['id'],
                    $category['name'],
                    $category['description'] ?? null,
                    $category['places'] ?? 1,
                    $category['places_extra'] ?? null,
                    $category['deck_id'] ?? null,
                    $category['ship_id'],
                    $category['meta_id'] ?? null,
                    $category['meta_name'] ?? null,
                    $extraData
                ]);
            }
            
            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Batch сохранение круизов
     * @param array $cruises Массив круизов, каждый элемент: ['id' => int, 'ship_id' => int, 'name' => string, ...]
     * @return void
     */
    public function saveCruisesBatch($cruises)
    {
        if (empty($cruises)) {
            return;
        }
        
        $this->pdo->beginTransaction();
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT OR REPLACE INTO cruises (
                    id, ship_id, name, route, date_start, date_end, days, nights,
                    waybill_data, schedule_html, description, extra_data, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");
            
            foreach ($cruises as $cruise) {
                // waybill_data должен быть в JSON формате
                $waybillData = $cruise['waybill_data'] ?? '[]';
                if (is_array($waybillData)) {
                    $waybillData = json_encode($waybillData, JSON_UNESCAPED_UNICODE);
                }
                
                $extraData = !empty($cruise['extra_data']) ? json_encode($cruise['extra_data'], JSON_UNESCAPED_UNICODE) : null;
                
                $stmt->execute([
                    $cruise['id'],
                    $cruise['ship_id'],
                    $cruise['name'],
                    $cruise['route'] ?? null,
                    $cruise['date_start'],
                    $cruise['date_end'],
                    $cruise['days'] ?? null,
                    $cruise['nights'] ?? null,
                    $waybillData,
                    $cruise['schedule_html'] ?? null,
                    $cruise['description'] ?? null,
                    $extraData
                ]);
            }
            
            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Batch сохранение цен
     * @param array $prices Массив цен, каждый элемент: ['cruise_id' => int, 'cabin_category_id' => int, 'price_value' => int, ...]
     * @return void
     */
    public function savePricesBatch($prices)
    {
        if (empty($prices)) {
            return;
        }
        
        $this->pdo->beginTransaction();
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO prices (
                    cruise_id, cabin_category_id, deck_id, price_value, price_extra,
                    places_qnt, nofull, tariff_name, extra_data
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($prices as $price) {
                $extraData = !empty($price['extra_data']) ? json_encode($price['extra_data'], JSON_UNESCAPED_UNICODE) : null;
                
                $stmt->execute([
                    $price['cruise_id'],
                    $price['cabin_category_id'],
                    $price['deck_id'] ?? null,
                    $price['price_value'],
                    $price['price_extra'] ?? null,
                    $price['places_qnt'] ?? 1,
                    $price['nofull'] ?? 0,
                    $price['tariff_name'] ?? null,
                    $extraData
                ]);
            }
            
            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Получение круиза по ID
     * @param int $cruiseId ID круиза
     * @return array|null
     */
    public function getCruiseById($cruiseId)
    {
        $stmt = $this->pdo->prepare("
            SELECT c.*, s.name as ship_name
            FROM cruises c
            LEFT JOIN ships s ON c.ship_id = s.id
            WHERE c.id = ?
        ");
        $stmt->execute([$cruiseId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Декодируем JSON поля если есть
        if ($result) {
            if (!empty($result['waybill_data'])) {
                $result['waybill_data'] = json_decode($result['waybill_data'], true);
            }
            if (!empty($result['extra_data'])) {
                $result['extra_data'] = json_decode($result['extra_data'], true);
            }
        }
        
        return $result ?: null;
    }

    /**
     * Получение всех теплоходов
     * @return array
     */
    public function getAllShips()
    {
        $stmt = $this->pdo->prepare("SELECT * FROM ships ORDER BY name");
        $stmt->execute();
        $ships = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Декодируем extra_data для каждого теплохода
        foreach ($ships as &$ship) {
            if (!empty($ship['extra_data'])) {
                $ship['extra_data'] = json_decode($ship['extra_data'], true);
            }
        }
        
        return $ships;
    }

    /**
     * Получение всех категорий кают
     * @return array
     */
    public function getAllCabinCategories()
    {
        $stmt = $this->pdo->prepare("SELECT * FROM cabin_categories ORDER BY name");
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Декодируем extra_data для каждой категории
        foreach ($categories as &$category) {
            if (!empty($category['extra_data'])) {
                $category['extra_data'] = json_decode($category['extra_data'], true);
            }
        }
        
        return $categories;
    }

    /**
     * Получение статистики базы данных
     * @return array
     */
    public function getStats()
    {
        $stats = [];
        
        // Количество теплоходов
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM ships");
        $stats['ships'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Количество палуб
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM decks");
        $stats['decks'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Количество категорий кают
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM cabin_categories");
        $stats['cabin_categories'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Количество круизов
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM cruises");
        $stats['cruises'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Количество цен
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM prices");
        $stats['prices'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        return $stats;
    }

    /**
     * Очистка всех данных
     * @return void
     */
    public function clearAll()
    {
        $this->pdo->exec("DELETE FROM prices");
        $this->pdo->exec("DELETE FROM cruises");
        $this->pdo->exec("DELETE FROM cabin_categories");
        $this->pdo->exec("DELETE FROM decks");
        $this->pdo->exec("DELETE FROM ships");
    }

    /**
     * Очистка круизов без цен (вызывается в конце фазы 1)
     * @return array Статистика очистки: ['total' => int, 'deleted' => int, 'remaining' => int]
     */
    public function cleanCruisesWithoutPrices()
    {
        try {
            $cruises = $this->getAllCruises();
            $totalCruises = count($cruises);
            $deletedCount = 0;
            
            $this->pdo->beginTransaction();
            
            foreach ($cruises as $cruise) {
                $cruiseId = $cruise['id'];
                
                // Проверяем, есть ли цены для этого круиза
                $prices = $this->getPricesByCruiseId($cruiseId);
                
                if (empty($prices)) {
                    // Удаляем сам круиз
                    $stmt = $this->pdo->prepare("DELETE FROM cruises WHERE id = ?");
                    $stmt->execute([$cruiseId]);
                    
                    $deletedCount++;
                }
            }
            
            $this->pdo->commit();
            
            return [
                'total' => $totalCruises,
                'deleted' => $deletedCount,
                'remaining' => $totalCruises - $deletedCount
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Ошибка при очистке круизов без цен: " . $e->getMessage());
        }
    }
}

