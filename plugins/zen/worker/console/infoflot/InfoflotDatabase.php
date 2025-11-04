<?php namespace Zen\Worker\Console\infoflot;

use PDO;
use Exception;

class InfoflotDatabase
{
    private $pdo;
    private $dbPath;

    public function __construct()
    {
        $this->dbPath = __DIR__ . '/infoflot_data.sqlite';
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
        // Таблица теплоходов (id = infoflot_ship_id)
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS ships (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL,
                type TEXT,
                operator_name TEXT,
                description TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Таблица палуб (id = infoflot_deck_id)
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS decks (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL,
                ship_id INTEGER,
                position INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (ship_id) REFERENCES ships(id)
            )
        ");

        // Таблица категорий кают (id = infoflot_type_id)
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS cabin_categories (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL,
                places INTEGER,
                deck_id INTEGER,
                ship_id INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (deck_id) REFERENCES decks(id),
                FOREIGN KEY (ship_id) REFERENCES ships(id)
            )
        ");

        // Таблица круизов (id = infoflot_cruise_id)
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS cruises (
                id INTEGER PRIMARY KEY,
                ship_id INTEGER,
                name TEXT NOT NULL,
                beautiful_name TEXT,
                route TEXT,
                route_short TEXT,
                date_start DATETIME,
                date_end DATETIME,
                date_start_timestamp INTEGER,
                date_end_timestamp INTEGER,
                days INTEGER,
                nights INTEGER,
                description TEXT,
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
                type_id INTEGER,
                type_name TEXT,
                price_adult INTEGER,
                price_default INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (cruise_id) REFERENCES cruises(id),
                FOREIGN KEY (cabin_category_id) REFERENCES cabin_categories(id)
            )
        ");

        // Таблица кают (для связи с категориями)
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS cabins (
                id INTEGER PRIMARY KEY,
                ship_id INTEGER,
                deck_id INTEGER,
                type_id INTEGER,
                name TEXT,
                places_main INTEGER,
                places_additional INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (ship_id) REFERENCES ships(id),
                FOREIGN KEY (deck_id) REFERENCES decks(id),
                FOREIGN KEY (type_id) REFERENCES cabin_categories(id)
            )
        ");

        // Создаем индексы для быстрого поиска
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_cruises_ship_id ON cruises(ship_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_prices_cruise_id ON prices(cruise_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_cabin_categories_ship_id ON cabin_categories(ship_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_cabins_ship_id ON cabins(ship_id)");
    }

    /**
     * Сохранение теплохода (id = infoflot_ship_id)
     */
    public function saveShip($infoflotShipId, $name, $type = null, $operatorName = null, $description = '')
    {
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO ships (id, name, type, operator_name, description, updated_at) 
            VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        return $stmt->execute([$infoflotShipId, $name, $type, $operatorName, $description]);
    }

    /**
     * Batch сохранение теплоходов
     */
    public function saveShipsBatch($ships)
    {
        $this->pdo->beginTransaction();
        
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO ships (id, name, type, operator_name, description, updated_at) 
            VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        
        foreach ($ships as $ship) {
            $stmt->execute([
                $ship['id'],
                $ship['name'],
                $ship['type'] ?? null,
                $ship['operator_name'] ?? null,
                $ship['description'] ?? ''
            ]);
        }
        
        $this->pdo->commit();
    }

    /**
     * Получение теплохода по Infoflot ID
     */
    public function getShipByInfoflotId($infoflotShipId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM ships WHERE id = ?");
        $stmt->execute([$infoflotShipId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Сохранение палубы (id = infoflot_deck_id)
     */
    public function saveDeck($infoflotDeckId, $name, $shipId, $position = null)
    {
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO decks (id, name, ship_id, position) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$infoflotDeckId, $name, $shipId, $position]);
    }

    /**
     * Сохранение категории кают (id = infoflot_type_id)
     */
    public function saveCabinCategory($infoflotTypeId, $name, $places, $deckId = null, $shipId = null)
    {
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO cabin_categories 
            (id, name, places, deck_id, ship_id) 
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$infoflotTypeId, $name, $places, $deckId, $shipId]);
    }

    /**
     * Сохранение круиза (id = infoflot_cruise_id, ship_id = infoflot_ship_id)
     */
    public function saveCruise($data)
    {
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO cruises (
                id, ship_id, name, beautiful_name, route, route_short,
                date_start, date_end, date_start_timestamp, date_end_timestamp,
                days, nights, description, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        
        return $stmt->execute([
            $data['infoflot_cruise_id'],
            $data['infoflot_ship_id'],
            $data['name'],
            $data['beautiful_name'] ?? null,
            $data['route'],
            $data['route_short'] ?? null,
            $data['date_start'],
            $data['date_end'],
            $data['date_start_timestamp'] ?? null,
            $data['date_end_timestamp'] ?? null,
            $data['days'] ?? null,
            $data['nights'] ?? null,
            $data['description'] ?? null
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
                id, ship_id, name, beautiful_name, route, route_short,
                date_start, date_end, date_start_timestamp, date_end_timestamp,
                days, nights, description, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        
        foreach ($cruises as $cruise) {
            $stmt->execute([
                $cruise['infoflot_cruise_id'],
                $cruise['infoflot_ship_id'],
                $cruise['name'],
                $cruise['beautiful_name'] ?? null,
                $cruise['route'],
                $cruise['route_short'] ?? null,
                $cruise['date_start'],
                $cruise['date_end'],
                $cruise['date_start_timestamp'] ?? null,
                $cruise['date_end_timestamp'] ?? null,
                $cruise['days'] ?? null,
                $cruise['nights'] ?? null,
                $cruise['description'] ?? null
            ]);
        }
        
        $this->pdo->commit();
    }

    /**
     * Сохранение цены
     */
    public function savePrice($cruiseId, $cabinCategoryId, $typeId, $typeName, $priceAdult, $priceDefault = null)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO prices (cruise_id, cabin_category_id, type_id, type_name, price_adult, price_default) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$cruiseId, $cabinCategoryId, $typeId, $typeName, $priceAdult, $priceDefault]);
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
            INSERT INTO prices (cruise_id, cabin_category_id, type_id, type_name, price_adult, price_default) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($prices as $price) {
            $stmt->execute([
                $price['cruise_id'],
                $price['cabin_category_id'],
                $price['type_id'],
                $price['type_name'],
                $price['price_adult'],
                $price['price_default'] ?? null
            ]);
        }
        
        $this->pdo->commit();
    }

    /**
     * Сохранение каюты
     */
    public function saveCabin($infoflotCabinId, $shipId, $deckId, $typeId, $name, $placesMain, $placesAdditional = 0)
    {
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO cabins (id, ship_id, deck_id, type_id, name, places_main, places_additional) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$infoflotCabinId, $shipId, $deckId, $typeId, $name, $placesMain, $placesAdditional]);
    }

    /**
     * Получение всех круизов с теплоходами
     */
    public function getAllCruises()
    {
        $stmt = $this->pdo->prepare("
            SELECT c.id as id, c.id as infoflot_cruise_id, c.ship_id as infoflot_ship_id, 
                   c.name, c.beautiful_name, c.route, c.route_short,
                   c.date_start, c.date_end, c.date_start_timestamp, c.date_end_timestamp,
                   c.days, c.nights, c.description,
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
     * Получение цен для круиза по ID
     */
    public function getPricesByCruiseId($cruiseId)
    {
        $stmt = $this->pdo->prepare("
            SELECT p.*, cc.name as category_name, cc.places, d.name as deck_name
            FROM prices p
            LEFT JOIN cabin_categories cc ON p.cabin_category_id = cc.id
            LEFT JOIN cabins c ON c.type_id = cc.id AND c.ship_id = (
                SELECT ship_id FROM cruises WHERE id = ?
            )
            LEFT JOIN decks d ON c.deck_id = d.id
            WHERE p.cruise_id = ?
        ");
        $stmt->execute([$cruiseId, $cruiseId]);
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
     * Получение всех теплоходов
     */
    public function getAllShips()
    {
        $stmt = $this->pdo->prepare("SELECT * FROM ships ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    // Удаляем круиз
                    $stmt = $this->pdo->prepare("DELETE FROM cruises WHERE id = ?");
                    $stmt->execute([$cruiseId]);
                    
                    // Удаляем связанные цены
                    $stmt = $this->pdo->prepare("DELETE FROM prices WHERE cruise_id = ?");
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

