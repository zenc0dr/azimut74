<?php namespace Zen\Worker\Console\waterway;

use PDO;
use Exception;

class WaterwayDatabase
{
    private $pdo;
    private $dbPath;

    public function __construct()
    {
        $this->dbPath = __DIR__ . '/waterway_data.sqlite';
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
            
            // Устанавливаем права доступа для базы данных
            if (file_exists($this->dbPath)) {
                chmod($this->dbPath, 0664);
            }
        } catch (Exception $e) {
            throw new Exception("Ошибка подключения к SQLite: " . $e->getMessage());
        }
    }

    /**
     * Создание таблиц
     */
    private function createTables()
    {
        // Таблица теплоходов (id = waterway_ship_id)
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS ships (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL,
                type TEXT,
                description TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Таблица круизов (id = waterway_cruise_id)
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS cruises (
                id INTEGER PRIMARY KEY,
                ship_id INTEGER,
                name TEXT,
                route TEXT,
                date_start DATETIME,
                date_end DATETIME,
                date_start_precise DATETIME,
                date_end_precise DATETIME,
                days INTEGER,
                description TEXT,
                schedule_html TEXT,
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
                cabin_category_name TEXT,
                cabin_category_desc TEXT,
                deck_name TEXT,
                price_value INTEGER,
                tariff_name TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (cruise_id) REFERENCES cruises(id)
            )
        ");

        // Создаем индексы для быстрого поиска
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_cruises_ship_id ON cruises(ship_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_prices_cruise_id ON prices(cruise_id)");
    }

    /**
     * Сохранение теплохода (id = waterway_ship_id)
     */
    public function saveShip($waterwayShipId, $name, $type = null, $description = '')
    {
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO ships (id, name, type, description, updated_at) 
            VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        return $stmt->execute([$waterwayShipId, $name, $type, $description]);
    }

    /**
     * Batch сохранение теплоходов
     */
    public function saveShipsBatch($ships)
    {
        $this->pdo->beginTransaction();
        
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO ships (id, name, type, description, updated_at) 
            VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        
        foreach ($ships as $ship) {
            $stmt->execute([
                $ship['id'],
                $ship['name'],
                $ship['type'] ?? null,
                $ship['description'] ?? ''
            ]);
        }
        
        $this->pdo->commit();
    }

    /**
     * Получение теплохода по Waterway ID
     */
    public function getShipByWaterwayId($waterwayShipId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM ships WHERE id = ?");
        $stmt->execute([$waterwayShipId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Сохранение круиза (id = waterway_cruise_id, ship_id = waterway_ship_id)
     */
    public function saveCruise($data)
    {
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO cruises (
                id, ship_id, name, route,
                date_start, date_end, date_start_precise, date_end_precise,
                days, description, schedule_html, waybill_data, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        
        return $stmt->execute([
            $data['waterway_cruise_id'],
            $data['waterway_ship_id'],
            $data['name'] ?? null,
            $data['route'] ?? null,
            $data['date_start'] ?? null,
            $data['date_end'] ?? null,
            $data['date_start_precise'] ?? null,
            $data['date_end_precise'] ?? null,
            $data['days'] ?? null,
            $data['description'] ?? null,
            $data['schedule_html'] ?? '',
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
                date_start, date_end, date_start_precise, date_end_precise,
                days, description, schedule_html, waybill_data, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        
        foreach ($cruises as $cruise) {
            $stmt->execute([
                $cruise['waterway_cruise_id'],
                $cruise['waterway_ship_id'],
                $cruise['name'] ?? null,
                $cruise['route'] ?? null,
                $cruise['date_start'] ?? null,
                $cruise['date_end'] ?? null,
                $cruise['date_start_precise'] ?? null,
                $cruise['date_end_precise'] ?? null,
                $cruise['days'] ?? null,
                $cruise['description'] ?? null,
                $cruise['schedule_html'] ?? '',
                $cruise['waybill_data'] ?? null
            ]);
        }
        
        $this->pdo->commit();
    }

    /**
     * Сохранение цены
     */
    public function savePrice($cruiseId, $cabinCategoryName, $cabinCategoryDesc, $deckName, $priceValue, $tariffName)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO prices (cruise_id, cabin_category_name, cabin_category_desc, deck_name, price_value, tariff_name) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$cruiseId, $cabinCategoryName, $cabinCategoryDesc, $deckName, $priceValue, $tariffName]);
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
            INSERT INTO prices (cruise_id, cabin_category_name, cabin_category_desc, deck_name, price_value, tariff_name) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($prices as $price) {
            $stmt->execute([
                $price['cruise_id'],
                $price['cabin_category_name'],
                $price['cabin_category_desc'] ?? null,
                $price['deck_name'] ?? null,
                $price['price_value'],
                $price['tariff_name']
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
            SELECT c.id as id, c.id as waterway_cruise_id, c.ship_id as waterway_ship_id, 
                   c.name, c.route, c.date_start, c.date_end, c.date_start_precise, c.date_end_precise,
                   c.days, c.description, c.schedule_html, c.waybill_data,
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
            SELECT * FROM prices WHERE cruise_id = ?
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
        
        return $stats;
    }

    /**
     * Очистка всех данных
     */
    public function clearAll()
    {
        $this->pdo->exec("DELETE FROM prices");
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

