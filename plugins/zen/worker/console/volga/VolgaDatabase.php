<?php namespace Zen\Worker\Console\volga;

use PDO;
use Exception;

class VolgaDatabase
{
    private $pdo;
    private $dbPath;

    public function __construct()
    {
        $this->dbPath = __DIR__ . '/volga_data.sqlite';
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
        // Таблица теплоходов (id = volga_ship_id)
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS ships (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Таблица палуб (id = volga_deck_id)
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS decks (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL,
                ship_id INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (ship_id) REFERENCES ships(id)
            )
        ");

        // Таблица категорий кают (id = volga_class_id)
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS cabin_categories (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL,
                comment TEXT,
                places_main_count INTEGER,
                places_extra_count INTEGER,
                deck_id INTEGER,
                ship_id INTEGER,
                no_full INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (deck_id) REFERENCES decks(id),
                FOREIGN KEY (ship_id) REFERENCES ships(id)
            )
        ");

        // Таблица кают (для связи class_id и deck_id)
        $this->pdo->exec("
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

        // Таблица круизов (id = volga_cruise_id)
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS cruises (
                id INTEGER PRIMARY KEY,
                ship_id INTEGER,
                name TEXT,
                route TEXT,
                begin_date TEXT,
                begin_time TEXT,
                end_date TEXT,
                end_time TEXT,
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
                price2_value INTEGER,
                nofull INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (cruise_id) REFERENCES cruises(id),
                FOREIGN KEY (cabin_category_id) REFERENCES cabin_categories(id)
            )
        ");

        // Таблица путевых листов
        $this->pdo->exec("
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

        // Создаем индексы для быстрого поиска
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_cruises_ship_id ON cruises(ship_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_prices_cruise_id ON prices(cruise_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_prices_cabin_category_id ON prices(cabin_category_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_waybills_cruise_id ON waybills(cruise_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_cabin_categories_ship_id ON cabin_categories(ship_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_cabins_class_id ON cabins(class_id)");
    }

    /**
     * Сохранение теплохода (id = volga_ship_id)
     */
    public function saveShip($volgaShipId, $name)
    {
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO ships (id, name, updated_at) 
            VALUES (?, ?, CURRENT_TIMESTAMP)
        ");
        return $stmt->execute([$volgaShipId, $name]);
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
     * Получение теплохода по Volga ID
     */
    public function getShipByVolgaId($volgaShipId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM ships WHERE id = ?");
        $stmt->execute([$volgaShipId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Сохранение палубы (id = volga_deck_id)
     */
    public function saveDeck($volgaDeckId, $name, $shipId)
    {
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO decks (id, name, ship_id) 
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$volgaDeckId, $name, $shipId]);
    }

    /**
     * Batch сохранение палуб
     */
    public function saveDecksBatch($decks)
    {
        $this->pdo->beginTransaction();
        
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO decks (id, name, ship_id) 
            VALUES (?, ?, ?)
        ");
        
        foreach ($decks as $deck) {
            $stmt->execute([$deck['id'], $deck['name'], $deck['ship_id']]);
        }
        
        $this->pdo->commit();
    }

    /**
     * Сохранение категории кают (id = volga_class_id)
     */
    public function saveCabinCategory($volgaClassId, $name, $comment, $placesMainCount, $placesExtraCount, $deckId = null, $shipId = null, $noFull = 0)
    {
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO cabin_categories 
            (id, name, comment, places_main_count, places_extra_count, deck_id, ship_id, no_full) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $volgaClassId, 
            $name, 
            $comment, 
            $placesMainCount, 
            $placesExtraCount, 
            $deckId, 
            $shipId,
            $noFull
        ]);
    }

    /**
     * Batch сохранение категорий кают
     */
    public function saveCabinCategoriesBatch($categories)
    {
        $this->pdo->beginTransaction();
        
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO cabin_categories 
            (id, name, comment, places_main_count, places_extra_count, deck_id, ship_id, no_full) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($categories as $category) {
            $stmt->execute([
                $category['id'],
                $category['name'],
                $category['comment'] ?? null,
                $category['places_main_count'],
                $category['places_extra_count'],
                $category['deck_id'] ?? null,
                $category['ship_id'] ?? null,
                $category['no_full'] ?? 0
            ]);
        }
        
        $this->pdo->commit();
    }

    /**
     * Сохранение каюты (связь class_id и deck_id)
     */
    public function saveCabin($volgaCabinId, $classId, $deckId, $shipId)
    {
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO cabins (id, class_id, deck_id, ship_id) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$volgaCabinId, $classId, $deckId, $shipId]);
    }

    /**
     * Batch сохранение кают
     */
    public function saveCabinsBatch($cabins)
    {
        $this->pdo->beginTransaction();
        
        $stmt = $this->pdo->prepare("
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
        
        $this->pdo->commit();
    }

    /**
     * Сохранение круиза (id = volga_cruise_id, ship_id = volga_ship_id)
     */
    public function saveCruise($data)
    {
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO cruises (
                id, ship_id, name, route, 
                begin_date, begin_time, end_date, end_time,
                date_start, date_end, waybill_data, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        
        return $stmt->execute([
            $data['volga_cruise_id'],
            $data['volga_ship_id'],
            $data['name'] ?? null,
            $data['route'] ?? null,
            $data['begin_date'] ?? null,
            $data['begin_time'] ?? null,
            $data['end_date'] ?? null,
            $data['end_time'] ?? null,
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
                begin_date, begin_time, end_date, end_time,
                date_start, date_end, waybill_data, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        
        foreach ($cruises as $cruise) {
            $stmt->execute([
                $cruise['volga_cruise_id'],
                $cruise['volga_ship_id'],
                $cruise['name'] ?? null,
                $cruise['route'] ?? null,
                $cruise['begin_date'] ?? null,
                $cruise['begin_time'] ?? null,
                $cruise['end_date'] ?? null,
                $cruise['end_time'] ?? null,
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
    public function savePrice($cruiseId, $cabinCategoryId, $priceValue, $price2Value = null, $nofull = 0)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO prices (cruise_id, cabin_category_id, price_value, price2_value, nofull) 
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$cruiseId, $cabinCategoryId, $priceValue, $price2Value, $nofull]);
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
            INSERT INTO prices (cruise_id, cabin_category_id, price_value, price2_value, nofull) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($prices as $price) {
            $stmt->execute([
                $price['cruise_id'],
                $price['cabin_category_id'],
                $price['price_value'],
                $price['price2_value'] ?? null,
                $price['nofull'] ?? 0
            ]);
        }
        
        $this->pdo->commit();
    }

    /**
     * Сохранение путевого листа
     */
    public function saveWaybill($cruiseId, $townName, $townId, $orderIndex, $bold = 0, $excursion = '')
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO waybills 
            (cruise_id, town_name, town_id, order_index, bold, excursion) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$cruiseId, $townName, $townId, $orderIndex, $bold, $excursion]);
    }

    /**
     * Batch сохранение путевых листов
     */
    public function saveWaybillsBatch($waybills)
    {
        if (empty($waybills)) {
            return;
        }
        
        $this->pdo->beginTransaction();
        
        $stmt = $this->pdo->prepare("
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
        
        $this->pdo->commit();
    }

    /**
     * Получение всех круизов с теплоходами
     */
    public function getAllCruises()
    {
        $stmt = $this->pdo->prepare("
            SELECT c.id as id, c.id as volga_cruise_id, c.ship_id as volga_ship_id, 
                   c.name, c.route, c.begin_date, c.begin_time, c.end_date, c.end_time,
                   c.date_start, c.date_end, c.waybill_data, 
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
            SELECT id as id, id as volga_cruise_id, ship_id as volga_ship_id, 
                   name, route, begin_date, begin_time, end_date, end_time,
                   date_start, date_end, waybill_data, 
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
            SELECT p.*, cc.name as category_name, cc.comment, 
                   cc.places_main_count, cc.places_extra_count,
                   d.name as deck_name
            FROM prices p
            LEFT JOIN cabin_categories cc ON p.cabin_category_id = cc.id
            LEFT JOIN cabins c ON c.class_id = cc.id
            LEFT JOIN decks d ON c.deck_id = d.id
            WHERE p.cruise_id = ?
            GROUP BY p.id, cc.id, d.id
        ");
        $stmt->execute([$cruiseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Получение путевого листа для круиза
     */
    public function getCruiseWaybill($cruiseId)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM waybills 
            WHERE cruise_id = ? 
            ORDER BY order_index
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
        
        // Количество палуб
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM decks");
        $stats['decks'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
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
        
        // Количество путевых листов
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM waybills");
        $stats['waybills'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        return $stats;
    }

    /**
     * Очистка всех данных
     */
    public function clearAll()
    {
        $this->pdo->exec("DELETE FROM waybills");
        $this->pdo->exec("DELETE FROM prices");
        $this->pdo->exec("DELETE FROM cabins");
        $this->pdo->exec("DELETE FROM cabin_categories");
        $this->pdo->exec("DELETE FROM decks");
        $this->pdo->exec("DELETE FROM cruises");
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
                    // Удаляем путевые листы
                    $stmt = $this->pdo->prepare("DELETE FROM waybills WHERE cruise_id = ?");
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

