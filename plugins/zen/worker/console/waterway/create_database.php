<?php
/**
 * Простой скрипт для создания базы данных Waterway
 * Запуск: php create_database.php
 */

$dbPath = __DIR__ . '/waterway_data.sqlite';

try {
    echo "🔧 Создание базы данных Waterway...\n";
    echo "Путь: $dbPath\n\n";
    
    // Создаём PDO подключение
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Таблица теплоходов (id = waterway_ship_id)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ships (
            id INTEGER PRIMARY KEY,
            name TEXT NOT NULL,
            type TEXT,
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Таблица ships создана\n";
    
    // Таблица круизов (id = waterway_cruise_id)
    $pdo->exec("
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
    echo "✅ Таблица cruises создана\n";
    
    // Таблица цен
    $pdo->exec("
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
    echo "✅ Таблица prices создана\n";
    
    // Создаем индексы для быстрого поиска
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_cruises_ship_id ON cruises(ship_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_prices_cruise_id ON prices(cruise_id)");
    echo "✅ Индексы созданы\n";
    
    // Проверяем структуру
    echo "\n📊 Проверка структуры таблиц:\n\n";
    
    // Таблица ships
    $stmt = $pdo->query("PRAGMA table_info(ships)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Таблица ships:\n";
    foreach ($columns as $col) {
        echo "  - {$col['name']} ({$col['type']})" . ($col['pk'] ? " PRIMARY KEY" : "") . "\n";
    }
    
    // Таблица cruises
    $stmt = $pdo->query("PRAGMA table_info(cruises)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\nТаблица cruises:\n";
    foreach ($columns as $col) {
        echo "  - {$col['name']} ({$col['type']})" . ($col['pk'] ? " PRIMARY KEY" : "") . "\n";
    }
    
    // Таблица prices
    $stmt = $pdo->query("PRAGMA table_info(prices)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\nТаблица prices:\n";
    foreach ($columns as $col) {
        echo "  - {$col['name']} ({$col['type']})" . ($col['pk'] ? " PRIMARY KEY" : "") . "\n";
    }
    
    // Статистика
    $stats = [
        'ships' => $pdo->query("SELECT COUNT(*) FROM ships")->fetchColumn(),
        'cruises' => $pdo->query("SELECT COUNT(*) FROM cruises")->fetchColumn(),
        'prices' => $pdo->query("SELECT COUNT(*) FROM prices")->fetchColumn(),
    ];
    
    echo "\n📈 Статистика:\n";
    echo "  Теплоходов: {$stats['ships']}\n";
    echo "  Круизов: {$stats['cruises']}\n";
    echo "  Цен: {$stats['prices']}\n";
    
    // Устанавливаем права доступа
    if (file_exists($dbPath)) {
        chmod($dbPath, 0664);
        echo "\n✅ Права доступа установлены (0664)\n";
    }
    
    echo "\n✅ База данных успешно создана и готова к использованию!\n";
    echo "💡 Теперь можно запустить парсер: php artisan worker:waterway-parse --clear\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    exit(1);
}


