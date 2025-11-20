<?php namespace Zen\Worker\Console\germes;

use PDO;
use Exception;

class GermesDatabase
{
    private $pdo;
    private $dbPath;

    public function __construct()
    {
        $this->dbPath = __DIR__ . '/germes_data.sqlite';
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
        } catch (Exception $e) {
            throw new Exception("Ошибка подключения к SQLite: " . $e->getMessage());
        }
    }

    /**
     * Создание таблиц
     */
    private function createTables()
    {
        // Таблица теплоходов (id = germes_ship_id)
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS ships (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Таблица палуб
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS decks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Таблица категорий кают (id = germes_class_id)
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS cabin_categories (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL,
                description TEXT,
                ship_id INTEGER,
                deck_id INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (ship_id) REFERENCES ships(id),
                FOREIGN KEY (deck_id) REFERENCES decks(id)
            )
        ");
        
        // Миграция: добавляем поле deck_id если его нет
        $this->migrateAddDeckId();

        // Таблица кают (pivot)
        // Временно убираем FOREIGN KEY, так как pivot может содержать ссылки на категории, которые ещё не сохранены
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS cabins (
                id INTEGER PRIMARY KEY,
                cabin_category_id INTEGER,
                number INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Миграция: добавляем поле number если его нет
        $this->migrateAddCabinNumber();

        // Таблица круизов (id = germes_cruise_id)
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS cruises (
                id INTEGER PRIMARY KEY,
                ship_id INTEGER,
                name TEXT,
                route TEXT,
                date_start DATETIME,
                date_end DATETIME,
                waybill_data TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (ship_id) REFERENCES ships(id)
            )
        ");

        // Таблица цен
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS prices (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                cruise_id INTEGER,
                cabin_category_id INTEGER,
                price_value INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (cruise_id) REFERENCES cruises(id),
                FOREIGN KEY (cabin_category_id) REFERENCES cabin_categories(id)
            )
        ");

        // Создаем индексы для быстрого поиска
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_cruises_ship_id ON cruises(ship_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_prices_cruise_id ON prices(cruise_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_prices_cabin_category_id ON prices(cabin_category_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_cabin_categories_ship_id ON cabin_categories(ship_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_cabin_categories_deck_id ON cabin_categories(deck_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_cabins_cabin_category_id ON cabins(cabin_category_id)");
    }

    /**
     * Миграция: добавление поля deck_id в cabin_categories
     */
    private function migrateAddDeckId()
    {
        try {
            // Проверяем, существует ли поле deck_id
            $stmt = $this->pdo->query("PRAGMA table_info(cabin_categories)");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $hasDeckId = false;
            foreach ($columns as $column) {
                if ($column['name'] === 'deck_id') {
                    $hasDeckId = true;
                    break;
                }
            }
            
            // Если поля нет, добавляем его
            if (!$hasDeckId) {
                $this->pdo->exec("ALTER TABLE cabin_categories ADD COLUMN deck_id INTEGER");
                $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_cabin_categories_deck_id ON cabin_categories(deck_id)");
            }
        } catch (\Exception $e) {
            // Игнорируем ошибки миграции, если таблица ещё не создана
        }
    }

    /**
     * Миграция: добавляем поле number в таблицу cabins
     */
    private function migrateAddCabinNumber()
    {
        try {
            // Проверяем, существует ли поле number
            $stmt = $this->pdo->query("PRAGMA table_info(cabins)");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $hasNumber = false;
            foreach ($columns as $column) {
                if ($column['name'] === 'number') {
                    $hasNumber = true;
                    break;
                }
            }
            
            // Если поля нет, добавляем его
            if (!$hasNumber) {
                $this->pdo->exec("ALTER TABLE cabins ADD COLUMN number INTEGER");
                $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_cabins_number ON cabins(number)");
            }
        } catch (\Exception $e) {
            // Игнорируем ошибки миграции, если таблица ещё не создана
        }
    }

    /**
     * Сохранение теплохода (id = germes_ship_id)
     */
    public function saveShip($germesShipId, $name)
    {
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO ships (id, name, updated_at) 
            VALUES (?, ?, CURRENT_TIMESTAMP)
        ");
        return $stmt->execute([$germesShipId, $name]);
    }

    /**
     * Batch сохранение теплоходов
     */
    public function saveShipsBatch($ships)
    {
        $this->pdo->beginTransaction();
        
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO ships (id, name, updated_at) 
            VALUES (?, ?, CURRENT_TIMESTAMP)
        ");
        
        foreach ($ships as $ship) {
            $stmt->execute([$ship['id'], $ship['name']]);
        }
        
        $this->pdo->commit();
    }

    /**
     * Получение теплохода по Germes ID
     */
    public function getShipByGermesId($germesShipId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM ships WHERE id = ?");
        $stmt->execute([$germesShipId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Сохранение палубы
     */
    public function saveDeck($name)
    {
        $stmt = $this->pdo->prepare("
            INSERT OR IGNORE INTO decks (name) 
            VALUES (?)
        ");
        $stmt->execute([$name]);
        
        // Получаем ID сохранённой палубы
        $stmt = $this->pdo->prepare("SELECT id FROM decks WHERE name = ?");
        $stmt->execute([$name]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['id'] : null;
    }

    /**
     * Получение палубы по названию
     */
    public function getDeckByName($name)
    {
        $stmt = $this->pdo->prepare("SELECT id FROM decks WHERE name = ?");
        $stmt->execute([$name]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['id'] : null;
    }

    /**
     * Сохранение категории кают (id = germes_class_id)
     */
    public function saveCabinCategory($germesClassId, $name, $description = null, $shipId = null, $deckId = null)
    {
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO cabin_categories 
            (id, name, description, ship_id, deck_id) 
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$germesClassId, $name, $description, $shipId, $deckId]);
    }

    /**
     * Batch сохранение категорий кают
     */
    public function saveCabinCategoriesBatch($categories)
    {
        $this->pdo->beginTransaction();
        
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO cabin_categories 
            (id, name, description, ship_id, deck_id) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($categories as $category) {
            $stmt->execute([
                $category['id'],
                $category['name'],
                $category['description'] ?? null,
                $category['ship_id'] ?? null,
                $category['deck_id'] ?? null
            ]);
        }
        
        $this->pdo->commit();
    }

    /**
     * Сохранение каюты (pivot)
     */
    public function saveCabin($germesCabinId, $cabinCategoryId, $cabinNumber = null)
    {
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO cabins (id, cabin_category_id, number) 
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$germesCabinId, $cabinCategoryId, $cabinNumber]);
    }

    /**
     * Batch сохранение кают
     */
    public function saveCabinsBatch($cabins)
    {
        $this->pdo->beginTransaction();
        
        $stmt = $this->pdo->prepare("
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
        
        $this->pdo->commit();
    }

    /**
     * Сохранение круиза (id = germes_cruise_id, ship_id = germes_ship_id)
     */
    public function saveCruise($data)
    {
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO cruises (
                id, ship_id, name, route, 
                date_start, date_end, waybill_data, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        
        return $stmt->execute([
            $data['germes_cruise_id'],
            $data['germes_ship_id'],
            $data['name'] ?? null,
            $data['route'] ?? null,
            $data['date_start'] ?? null,
            $data['date_end'] ?? null,
            $data['waybill_data'] ?? null
        ]);
    }

    /**
     * Batch сохранение круизов
     */
    public function saveCruisesBatch($cruises)
    {
        $this->pdo->beginTransaction();
        
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO cruises (
                id, ship_id, name, route, 
                date_start, date_end, waybill_data, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        
        foreach ($cruises as $cruise) {
            $stmt->execute([
                $cruise['germes_cruise_id'],
                $cruise['germes_ship_id'],
                $cruise['name'] ?? null,
                $cruise['route'] ?? null,
                $cruise['date_start'] ?? null,
                $cruise['date_end'] ?? null,
                $cruise['waybill_data'] ?? null
            ]);
        }
        
        $this->pdo->commit();
    }

    /**
     * Сохранение цены
     */
    public function savePrice($cruiseId, $cabinCategoryId, $priceValue)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO prices (cruise_id, cabin_category_id, price_value) 
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$cruiseId, $cabinCategoryId, $priceValue]);
    }

    /**
     * Batch сохранение цен
     */
    public function savePricesBatch($prices)
    {
        if (empty($prices)) {
            return;
        }
        
        $this->pdo->beginTransaction();
        
        $stmt = $this->pdo->prepare("
            INSERT INTO prices (cruise_id, cabin_category_id, price_value) 
            VALUES (?, ?, ?)
        ");
        
        foreach ($prices as $price) {
            $stmt->execute([
                $price['cruise_id'],
                $price['cabin_category_id'],
                $price['price_value']
            ]);
        }
        
        $this->pdo->commit();
    }

    /**
     * Получение всех круизов с теплоходами
     */
    public function getAllCruises()
    {
        $stmt = $this->pdo->prepare("
            SELECT c.id as id, c.id as germes_cruise_id, c.ship_id as germes_ship_id, 
                   c.name, c.route, c.date_start, c.date_end, c.waybill_data, 
                   c.created_at, c.updated_at,
                   s.name as ship_name 
            FROM cruises c 
            LEFT JOIN ships s ON c.ship_id = s.id 
            ORDER BY c.date_start
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Получение круиза по ID
     */
    public function getCruiseById($cruiseId)
    {
        $stmt = $this->pdo->prepare("
            SELECT id as id, id as germes_cruise_id, ship_id as germes_ship_id, 
                   name, route, date_start, date_end, waybill_data, 
                   created_at, updated_at
            FROM cruises WHERE id = ?
        ");
        $stmt->execute([$cruiseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Получение цен для круиза по ID
     */
    public function getPricesByCruiseId($cruiseId)
    {
        $stmt = $this->pdo->prepare("
            SELECT p.*, 
                   cc.name as category_name, 
                   cc.description,
                   d.name as deck_name
            FROM prices p
            LEFT JOIN cabin_categories cc ON p.cabin_category_id = cc.id
            LEFT JOIN decks d ON cc.deck_id = d.id
            WHERE p.cruise_id = ?
        ");
        $stmt->execute([$cruiseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Получение статистики
     */
    public function getStats()
    {
        $stats = [];
        
        // Количество теплоходов
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM ships");
        $stats['ships'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Количество круизов
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM cruises");
        $stats['cruises'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Количество цен
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM prices");
        $stats['prices'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Количество категорий кают
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM cabin_categories");
        $stats['cabin_categories'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Количество кают
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM cabins");
        $stats['cabins'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Количество палуб
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM decks");
        $stats['decks'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        return $stats;
    }

    /**
     * Очистка всех данных
     */
    public function clearAll()
    {
        $this->pdo->exec("DELETE FROM prices");
        $this->pdo->exec("DELETE FROM cabins");
        $this->pdo->exec("DELETE FROM cabin_categories");
        $this->pdo->exec("DELETE FROM cruises");
        $this->pdo->exec("DELETE FROM decks");
        $this->pdo->exec("DELETE FROM ships");
    }

    /**
     * Получение пути к базе данных
     */
    public function getDbPath()
    {
        return $this->dbPath;
    }

    /**
     * Получение PDO объекта
     */
    public function getPdo()
    {
        return $this->pdo;
    }

    /**
     * Обновление ship_id в cabin_categories на основе данных из круизов и цен
     * Используется для восстановления связей после обработки круизов
     */
    public function updateCabinCategoriesShipId()
    {
        try {
            $this->pdo->beginTransaction();
            
            // Получаем маппинг category_id -> ship_id из цен и круизов
            $stmt = $this->pdo->query("
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
            $updateStmt = $this->pdo->prepare("
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
            
            $this->pdo->commit();
            
            return $updated;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
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
            $this->pdo->beginTransaction();
            
            $stmt = $this->pdo->prepare("
                UPDATE cabin_categories 
                SET deck_id = ? 
                WHERE id = ? AND (deck_id IS NULL OR deck_id = 0)
            ");
            
            $updated = 0;
            foreach ($categoryToDeckMap as $categoryId => $deckId) {
                if ($deckId !== null && $deckId > 0) {
                    $stmt->execute([$deckId, $categoryId]);
                    $updated += $stmt->rowCount();
                }
            }
            
            $this->pdo->commit();
            
            return $updated;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
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
            $this->pdo->exec("
                DELETE FROM prices 
                WHERE cabin_category_id IN (
                    SELECT id FROM cabin_categories WHERE ship_id IS NULL
                )
            ");
            
            // Затем удаляем сами категории
            $stmt = $this->pdo->query("
                SELECT COUNT(*) as count FROM cabin_categories WHERE ship_id IS NULL
            ");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            $this->pdo->exec("
                DELETE FROM cabin_categories WHERE ship_id IS NULL
            ");
            
            return (int)$count;
        } catch (\Exception $e) {
            throw new \Exception("Ошибка при удалении категорий без теплохода: " . $e->getMessage());
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
                    // Удаляем цены
                    $stmt = $this->pdo->prepare("DELETE FROM prices WHERE cruise_id = ?");
                    $stmt->execute([$cruiseId]);
                    
                    // Удаляем сам круиз
                    $stmt = $this->pdo->prepare("DELETE FROM cruises WHERE id = ?");
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

