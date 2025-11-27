<?php namespace Zen\Worker\Console\unified;

use Illuminate\Console\Command;
use Zen\Worker\Console\waterway\WaterwayDatabase;
use Zen\Worker\Console\gama\GamaDatabase;
use Zen\Worker\Console\germes\GermesDatabase;
use Zen\Worker\Console\infoflot\InfoflotDatabase;
use Zen\Worker\Console\volga\VolgaDatabase;

/**
 * Команда для тестирования единой структуры SQLite баз
 */
class TestUnifiedDatabases extends Command
{
    protected $name = 'worker:test-unified-databases';
    protected $description = 'Тестирование единой структуры SQLite баз данных';

    /**
     * Выполнение команды
     */
    public function handle()
    {
        $this->info('=== Тестирование единой структуры SQLite баз ===');
        $this->line('');

        $sources = [
            'waterway' => WaterwayDatabase::class,
            'gama' => GamaDatabase::class,
            'germes' => GermesDatabase::class,
            'infoflot' => InfoflotDatabase::class,
            'volga' => VolgaDatabase::class,
        ];

        $results = [];
        
        foreach ($sources as $sourceName => $databaseClass) {
            $this->info("Тестирование {$sourceName}...");
            
            try {
                $db = new $databaseClass();
                $results[$sourceName] = $this->testDatabase($db, $sourceName);
            } catch (\Exception $e) {
                $this->error("Ошибка при тестировании {$sourceName}: " . $e->getMessage());
                $results[$sourceName] = false;
            }
            
            $this->line('');
        }

        // Итоговый отчет
        $this->info('=== Итоговый отчет ===');
        $this->line('');
        
        $successCount = 0;
        foreach ($results as $sourceName => $success) {
            if ($success) {
                $this->info("✅ {$sourceName}: OK");
                $successCount++;
            } else {
                $this->error("❌ {$sourceName}: FAILED");
            }
        }
        
        $this->line('');
        $this->info("Успешно: {$successCount}/" . count($results));
        
        return $successCount === count($results) ? 0 : 1;
    }

    /**
     * Тестирование конкретной базы данных
     */
    private function testDatabase($db, $sourceName)
    {
        $success = true;
        
        // 1. Проверка структуры таблиц
        $this->line("  Проверка структуры таблиц...");
        if (!$this->checkTableStructure($db)) {
            $this->error("    ❌ Ошибка в структуре таблиц");
            $success = false;
        } else {
            $this->info("    ✅ Структура таблиц корректна");
        }
        
        // 2. Проверка методов сохранения
        $this->line("  Проверка методов сохранения...");
        if (!$this->testSaveMethods($db, $sourceName)) {
            $this->error("    ❌ Ошибка в методах сохранения");
            $success = false;
        } else {
            $this->info("    ✅ Методы сохранения работают");
        }
        
        // 3. Проверка методов получения
        $this->line("  Проверка методов получения...");
        if (!$this->testGetMethods($db)) {
            $this->error("    ❌ Ошибка в методах получения");
            $success = false;
        } else {
            $this->info("    ✅ Методы получения работают");
        }
        
        // 4. Проверка специфичных функций
        $this->line("  Проверка специфичных функций...");
        if (!$this->testSpecificFeatures($db, $sourceName)) {
            $this->error("    ❌ Ошибка в специфичных функциях");
            $success = false;
        } else {
            $this->info("    ✅ Специфичные функции работают");
        }
        
        return $success;
    }

    /**
     * Проверка структуры таблиц
     */
    private function checkTableStructure($db)
    {
        $requiredTables = ['ships', 'decks', 'cabin_categories', 'cruises', 'prices'];
        
        foreach ($requiredTables as $table) {
            if (!$db->tableExists($table)) {
                $this->error("      Таблица {$table} не найдена");
                return false;
            }
        }
        
        return true;
    }

    /**
     * Тестирование методов сохранения
     */
    private function testSaveMethods($db, $sourceName)
    {
        try {
            // Тест saveShip
            $testShipId = 999999;
            $result = $db->saveShip($testShipId, 'Test Ship');
            
            // Проверяем, что ship_id действительно сохранен
            $savedShip = $db->getShipBySourceId($testShipId);
            if (!$savedShip || $savedShip['id'] != $testShipId) {
                throw new \Exception("Ship не сохранен правильно");
            }
            
            // Тест saveDeck
            $testDeckId = 999999;
            // Для разных источников разные сигнатуры saveDeck
            if ($sourceName === 'germes') {
                // Germes использует старый формат (только имя), но новый интерфейс требует id и name
                // Используем числовой hash как ID (первые 8 символов md5 hash)
                $deckHash = abs(hexdec(substr(md5('Test Deck'), 0, 8)));
                $db->saveDeck($deckHash, 'Test Deck');
                $testDeckId = $deckHash; // Используем hash для очистки
            } else {
                $db->saveDeck($testDeckId, 'Test Deck');
            }
            
            // Тест saveCabinCategory (ship_id обязателен, передаем как третий параметр)
            $testCategoryId = 999999;
            // Проверяем, что ship_id не null (должен быть сохранен выше)
            if ($testShipId === null) {
                throw new \Exception("ship_id обязателен для категории кают");
            }
            $db->saveCabinCategory($testCategoryId, 'Test Category', $testShipId, ['places' => 2]);
            
            // Очистка тестовых данных
            $pdo = $db->getPdo();
            $pdo->exec("DELETE FROM cabin_categories WHERE id = {$testCategoryId}");
            $pdo->exec("DELETE FROM decks WHERE id = {$testDeckId}");
            $pdo->exec("DELETE FROM ships WHERE id = {$testShipId}");
            
            return true;
        } catch (\Exception $e) {
            $this->error("      Ошибка: " . $e->getMessage());
            $this->error("      Файл: " . basename($e->getFile()) . ":" . $e->getLine());
            $trace = $e->getTrace();
            if (!empty($trace[0])) {
                $this->error("      Метод: " . ($trace[0]['class'] ?? '') . "::" . ($trace[0]['function'] ?? ''));
            }
            return false;
        }
    }

    /**
     * Тестирование методов получения
     */
    private function testGetMethods($db)
    {
        try {
            // Тест getAllCruises
            $cruises = $db->getAllCruises();
            if (!is_array($cruises)) {
                return false;
            }
            
            // Тест getShipBySourceId (если есть круизы)
            if (!empty($cruises)) {
                $cruise = $cruises[0];
                if (isset($cruise['ship_id'])) {
                    $ship = $db->getShipBySourceId($cruise['ship_id']);
                    if (!$ship) {
                        return false;
                    }
                }
            }
            
            return true;
        } catch (\Exception $e) {
            $this->error("      Ошибка: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Тестирование специфичных функций
     */
    private function testSpecificFeatures($db, $sourceName)
    {
        try {
            switch ($sourceName) {
                case 'volga':
                    // Проверка таблицы cabins
                    if (!$db->tableExists('cabins')) {
                        $this->error("      Таблица cabins не найдена");
                        return false;
                    }
                    // Проверка таблицы waybills
                    if (!$db->tableExists('waybills')) {
                        $this->error("      Таблица waybills не найдена");
                        return false;
                    }
                    break;
                    
                case 'germes':
                    // Проверка таблицы cabins
                    if (!$db->tableExists('cabins')) {
                        $this->error("      Таблица cabins не найдена");
                        return false;
                    }
                    break;
            }
            
            return true;
        } catch (\Exception $e) {
            $this->error("      Ошибка: " . $e->getMessage());
            return false;
        }
    }
}

