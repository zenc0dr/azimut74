<?php namespace Zen\Worker\Console\gama;

use PDO;
use Exception;

class GamaDatabase
{
    private $pdo;
    private $dbPath;

    public function __construct()
    {
        $this->dbPath = __DIR__ . '/gama_data.sqlite';
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
        // Таблица теплоходов (id = gama_ship_id)
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS ships (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL,
                description TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Таблица палуб (id = gama_deck_id)
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS decks (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL,
                ship_id INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (ship_id) REFERENCES ships(id)
            )
        ");

        // Таблица категорий кают (id = gama_category_id)
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

        // Таблица круизов (id = gama_cruise_id)
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS cruises (
                id INTEGER PRIMARY KEY,
                ship_id INTEGER,
                name TEXT NOT NULL,
                route_name TEXT,
                date_start DATETIME,
                date_end DATETIME,
                path_s_id INTEGER,
                path_f_id INTEGER,
                waybill_data TEXT,
                schedule_html TEXT,
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
                price_a INTEGER,
                price_b INTEGER,
                persons INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (cruise_id) REFERENCES cruises(id),
                FOREIGN KEY (cabin_category_id) REFERENCES cabin_categories(id)
            )
        ");

        // Таблица маршрутов (путевых листов)
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS waybills (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                cruise_id INTEGER,
                town_name TEXT,
                town_id INTEGER,
                arrival_time DATETIME,
                departure_time DATETIME,
                is_bold INTEGER DEFAULT 0,
                order_index INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (cruise_id) REFERENCES cruises(id)
            )
        ");

        // Создаем индексы для быстрого поиска
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_cruises_ship_id ON cruises(ship_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_prices_cruise_id ON prices(cruise_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_waybills_cruise_id ON waybills(cruise_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_cabin_categories_ship_id ON cabin_categories(ship_id)");
    }

    /**
     * Сохранение теплохода (id = gama_ship_id)
     */
    public function saveShip($gamaShipId, $name, $description = '')
    {
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO ships (id, name, description, updated_at) 
            VALUES (?, ?, ?, CURRENT_TIMESTAMP)
        ");
        return $stmt->execute([$gamaShipId, $name, $description]);
    }

    /**
     * Batch сохранение теплоходов
     */
    public function saveShipsBatch($ships)
    {
        $this->pdo->beginTransaction();
        
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO ships (id, name, description, updated_at) 
            VALUES (?, ?, ?, CURRENT_TIMESTAMP)
        ");
        
        foreach ($ships as $ship) {
            $stmt->execute([$ship['id'], $ship['name'], $ship['description'] ?? '']);
        }
        
        $this->pdo->commit();
    }

    /**
     * Получение теплохода по Gama ID
     */
    public function getShipByGamaId($gamaShipId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM ships WHERE id = ?");
        $stmt->execute([$gamaShipId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Сохранение круиза (id = gama_cruise_id, ship_id = gama_ship_id)
     */
    public function saveCruise($data)
    {
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO cruises (
                id, ship_id, name, route_name, 
                date_start, date_end, path_s_id, path_f_id, 
                waybill_data, schedule_html, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        
        return $stmt->execute([
            $data['gama_cruise_id'],
            $data['gama_ship_id'],
            $data['name'],
            $data['route_name'],
            $data['date_start'],
            $data['date_end'],
            $data['path_s_id'],
            $data['path_f_id'],
            $data['waybill_data'],
            $data['schedule_html']
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
                id, ship_id, name, route_name, 
                date_start, date_end, path_s_id, path_f_id, 
                waybill_data, schedule_html, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        
        foreach ($cruises as $cruise) {
            $stmt->execute([
                $cruise['gama_cruise_id'],
                $cruise['gama_ship_id'],
                $cruise['name'],
                $cruise['route_name'],
                $cruise['date_start'],
                $cruise['date_end'],
                $cruise['path_s_id'],
                $cruise['path_f_id'],
                $cruise['waybill_data'],
                $cruise['schedule_html']
            ]);
        }
        
        $this->pdo->commit();
    }

    /**
     * Сохранение палубы (id = gama_deck_id)
     */
    public function saveDeck($gamaDeckId, $name, $shipId)
    {
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO decks (id, name, ship_id) 
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$gamaDeckId, $name, $shipId]);
    }

    /**
     * Сохранение категории кают (id = gama_category_id)
     */
    public function saveCabinCategory($gamaCategoryId, $name, $places, $deckId, $shipId)
    {
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO cabin_categories 
            (id, name, places, deck_id, ship_id) 
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$gamaCategoryId, $name, $places, $deckId, $shipId]);
    }

    /**
     * Сохранение цены (price_a вместо price_1, price_b вместо price_2)
     */
    public function savePrice($cruiseId, $cabinCategoryId, $priceA, $priceB = null, $persons = null)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO prices (cruise_id, cabin_category_id, price_a, price_b, persons) 
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$cruiseId, $cabinCategoryId, $priceA, $priceB, $persons]);
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
            INSERT INTO prices (cruise_id, cabin_category_id, price_a, price_b, persons) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($prices as $price) {
            $stmt->execute([
                $price['cruise_id'],
                $price['cabin_category_id'],
                $price['price_a'],
                $price['price_b'] ?? null,
                $price['persons'] ?? null
            ]);
        }
        
        $this->pdo->commit();
    }

    /**
     * Сохранение путевого листа
     */
    public function saveWaybill($cruiseId, $townName, $townId, $arrivalTime, $departureTime, $isBold, $orderIndex)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO waybills 
            (cruise_id, town_name, town_id, arrival_time, departure_time, is_bold, order_index) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$cruiseId, $townName, $townId, $arrivalTime, $departureTime, $isBold, $orderIndex]);
    }

    /**
     * Получение всех круизов с теплоходами
     */
    public function getAllCruises()
    {
        $stmt = $this->pdo->prepare("
            SELECT c.id as id, c.id as gama_cruise_id, c.ship_id as gama_ship_id, c.name, c.route_name, 
                   c.date_start, c.date_end, c.path_s_id, c.path_f_id, 
                   c.waybill_data, c.schedule_html, c.created_at, c.updated_at,
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
            SELECT id as id, id as gama_cruise_id, ship_id as gama_ship_id, name, route_name, 
                   date_start, date_end, path_s_id, path_f_id, 
                   waybill_data, schedule_html, created_at, updated_at
            FROM cruises WHERE id = ?
        ");
        $stmt->execute([$cruiseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Получение круиза по gama_cruise_id
     */
    public function getCruiseByGamaId($gamaCruiseId)
    {
        $stmt = $this->pdo->prepare("
            SELECT id as id, id as gama_cruise_id, ship_id as gama_ship_id, name, route_name, 
                   date_start, date_end, path_s_id, path_f_id, 
                   waybill_data, schedule_html, created_at, updated_at
            FROM cruises WHERE id = ?
        ");
        $stmt->execute([$gamaCruiseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Получение цен для круиза
     */
    public function getCruisePrices($cruiseId)
    {
        $stmt = $this->pdo->prepare("
            SELECT p.*, cc.name as category_name, cc.places, d.name as deck_name
            FROM prices p
            LEFT JOIN cabin_categories cc ON p.cabin_category_id = cc.id
            LEFT JOIN decks d ON cc.deck_id = d.id
            WHERE p.cruise_id = ?
            ORDER BY p.price_a
        ");
        $stmt->execute([$cruiseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Получение цен для круиза по ID (для проверки наличия цен)
     */
    public function getPricesByCruiseId($cruiseId)
    {
        $stmt = $this->pdo->prepare("
            SELECT p.*, cc.name as category_name, cc.places, d.name as deck_name
            FROM prices p
            LEFT JOIN cabin_categories cc ON p.cabin_category_id = cc.id
            LEFT JOIN decks d ON cc.deck_id = d.id
            WHERE p.cruise_id = ?
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
        $this->pdo->exec("DELETE FROM waybills");
        $this->pdo->exec("DELETE FROM prices");
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
     * Получение количества цен для круиза
     */
    public function getCruisePricesCount($cruiseId)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM prices WHERE cruise_id = ?");
        $stmt->execute([$cruiseId]);
        return $stmt->fetchColumn();
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
     * Получение всех категорий кают
     */
    public function getAllCabinCategories()
    {
        $stmt = $this->pdo->prepare("SELECT * FROM cabin_categories ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Получение всех цен
     */
    public function getAllPrices()
    {
        $stmt = $this->pdo->prepare("SELECT * FROM prices ORDER BY cruise_id, cabin_category_id");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Получение всех путевых листов
     */
    public function getAllWaybills()
    {
        $stmt = $this->pdo->prepare("SELECT * FROM waybills ORDER BY cruise_id, order_index");
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
            $this->pdo->beginTransaction();
            
            // Удаляем цены
            $stmt = $this->pdo->prepare("DELETE FROM prices WHERE cruise_id = ?");
            $stmt->execute([$gamaCruiseId]);
            
            // Удаляем путевые листы
            $stmt = $this->pdo->prepare("DELETE FROM waybills WHERE cruise_id = ?");
            $stmt->execute([$gamaCruiseId]);
            
            // Удаляем сам круиз
            $stmt = $this->pdo->prepare("DELETE FROM cruises WHERE id = ?");
            $stmt->execute([$gamaCruiseId]);
            
            // Подтверждаем транзакцию
            $this->pdo->commit();
            
            return true;
            
        } catch (\Exception $e) {
            // Откатываем транзакцию при ошибке
            $this->pdo->rollBack();
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
