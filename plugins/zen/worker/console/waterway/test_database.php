<?php
/**
 * Тестовый скрипт для проверки структуры базы данных Waterway
 * Запуск: php test_database.php
 */

require_once __DIR__ . '/../../../../bootstrap/autoload.php';

use Zen\Worker\Console\waterway\WaterwayDatabase;

try {
    echo "🔧 Создание базы данных Waterway...\n";
    
    $db = new WaterwayDatabase();
    
    echo "✅ База данных создана: " . $db->getDbPath() . "\n";
    
    // Проверяем структуру
    $pdo = $db->getPdo();
    
    echo "\n📊 Проверка структуры таблиц:\n";
    
    // Проверяем таблицу ships
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='ships'");
    if ($stmt->fetch()) {
        echo "✅ Таблица ships существует\n";
        $stmt = $pdo->query("PRAGMA table_info(ships)");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "   Колонки: " . implode(', ', array_column($columns, 'name')) . "\n";
    } else {
        echo "❌ Таблица ships не найдена\n";
    }
    
    // Проверяем таблицу cruises
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='cruises'");
    if ($stmt->fetch()) {
        echo "✅ Таблица cruises существует\n";
        $stmt = $pdo->query("PRAGMA table_info(cruises)");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "   Колонки: " . implode(', ', array_column($columns, 'name')) . "\n";
    } else {
        echo "❌ Таблица cruises не найдена\n";
    }
    
    // Проверяем таблицу prices
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='prices'");
    if ($stmt->fetch()) {
        echo "✅ Таблица prices существует\n";
        $stmt = $pdo->query("PRAGMA table_info(prices)");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "   Колонки: " . implode(', ', array_column($columns, 'name')) . "\n";
    } else {
        echo "❌ Таблица prices не найдена\n";
    }
    
    // Проверяем статистику
    $stats = $db->getStats();
    echo "\n📈 Статистика:\n";
    echo "   Теплоходов: {$stats['ships']}\n";
    echo "   Круизов: {$stats['cruises']}\n";
    echo "   Цен: {$stats['prices']}\n";
    
    echo "\n✅ База данных готова к использованию!\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    exit(1);
}

