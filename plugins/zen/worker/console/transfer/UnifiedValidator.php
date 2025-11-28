<?php namespace Zen\Worker\Console\transfer;

/**
 * Единый валидатор для всех источников
 * Работает с единой структурой SQLite баз
 */
class UnifiedValidator extends TransferValidator
{
    /**
     * Проверка структуры базы данных
     */
    protected function validateStructure()
    {
        $requiredTables = ['ships', 'cruises', 'cabin_categories', 'prices', 'decks'];
        
        foreach ($requiredTables as $table) {
            if (!$this->tableExists($table)) {
                $this->addError("Таблица $table не найдена");
            }
        }
        
        // Проверка наличия данных в обязательных таблицах
        $this->validateTableData();
    }
    
    /**
     * Проверка наличия данных в таблицах
     */
    protected function validateTableData()
    {
        // Проверка наличия теплоходов
        $shipsCount = $this->query("SELECT COUNT(*) as count FROM ships");
        if (empty($shipsCount) || (int)$shipsCount[0]['count'] === 0) {
            $this->addError("Таблица ships пуста");
        }
        
        // Проверка наличия круизов
        $cruisesCount = $this->query("SELECT COUNT(*) as count FROM cruises");
        if (empty($cruisesCount) || (int)$cruisesCount[0]['count'] === 0) {
            $this->addWarning("Таблица cruises пуста");
        }
        
        // Проверка наличия категорий кают
        $categoriesCount = $this->query("SELECT COUNT(*) as count FROM cabin_categories");
        if (empty($categoriesCount) || (int)$categoriesCount[0]['count'] === 0) {
            $this->addWarning("Таблица cabin_categories пуста");
        }
    }
    
    /**
     * Проверка целостности данных
     */
    protected function validateIntegrity()
    {
        // Круизы без теплоходов
        $orphanedCruises = $this->query("
            SELECT c.id FROM cruises c 
            LEFT JOIN ships s ON c.ship_id = s.id 
            WHERE s.id IS NULL
        ");
        
        if (count($orphanedCruises) > 0) {
            $this->addError("Найдены круизы без теплоходов", [
                'count' => count($orphanedCruises),
                'cruise_ids' => array_column($orphanedCruises, 'id')
            ]);
        }
        
        // Цены без круизов
        $orphanedPrices = $this->query("
            SELECT p.id FROM prices p 
            LEFT JOIN cruises c ON p.cruise_id = c.id 
            WHERE c.id IS NULL
        ");
        
        if (count($orphanedPrices) > 0) {
            $this->addError("Найдены цены без круизов", [
                'count' => count($orphanedPrices)
            ]);
        }
        
        // Цены без категорий кают
        $orphanedPrices = $this->query("
            SELECT p.id FROM prices p 
            LEFT JOIN cabin_categories cc ON p.cabin_category_id = cc.id 
            WHERE cc.id IS NULL
        ");
        
        if (count($orphanedPrices) > 0) {
            $this->addError("Найдены цены без категорий кают", [
                'count' => count($orphanedPrices)
            ]);
        }
        
        // Цены с указанным deck_id, но без палубы
        $pricesWithInvalidDeck = $this->query("
            SELECT p.id FROM prices p 
            WHERE p.deck_id IS NOT NULL 
            AND NOT EXISTS (
                SELECT 1 FROM decks d WHERE d.id = p.deck_id
            )
        ");
        
        if (count($pricesWithInvalidDeck) > 0) {
            $this->addError("Найдены цены с несуществующими палубами", [
                'count' => count($pricesWithInvalidDeck)
            ]);
        }
        
        // Категории кают без теплоходов
        $orphanedCategories = $this->query("
            SELECT cc.id FROM cabin_categories cc 
            LEFT JOIN ships s ON cc.ship_id = s.id 
            WHERE s.id IS NULL
        ");
        
        if (count($orphanedCategories) > 0) {
            $this->addError("Найдены категории кают без теплоходов", [
                'count' => count($orphanedCategories)
            ]);
        }
    }
    
    /**
     * Проверка валидности данных
     */
    protected function validateData()
    {
        // Круизы без дат
        $cruisesWithoutDates = $this->query("
            SELECT id FROM cruises 
            WHERE date_start IS NULL OR date_end IS NULL
        ");
        
        if (count($cruisesWithoutDates) > 0) {
            $this->addError("Найдены круизы без дат", [
                'count' => count($cruisesWithoutDates),
                'cruise_ids' => array_column($cruisesWithoutDates, 'id')
            ]);
        }
        
        // Круизы с некорректными датами (date_end раньше date_start)
        $cruisesWithInvalidDates = $this->query("
            SELECT id FROM cruises 
            WHERE date_start IS NOT NULL 
            AND date_end IS NOT NULL 
            AND date_end < date_start
        ");
        
        if (count($cruisesWithInvalidDates) > 0) {
            $this->addError("Найдены круизы с некорректными датами (дата окончания раньше даты начала)", [
                'count' => count($cruisesWithInvalidDates),
                'cruise_ids' => array_column($cruisesWithInvalidDates, 'id')
            ]);
        }
        
        // Круизы без waybill_data
        $cruisesWithoutWaybill = $this->query("
            SELECT id FROM cruises 
            WHERE waybill_data IS NULL OR waybill_data = ''
        ");
        
        if (count($cruisesWithoutWaybill) > 0) {
            $this->addError("Найдены круизы без маршрута", [
                'count' => count($cruisesWithoutWaybill),
                'cruise_ids' => array_column($cruisesWithoutWaybill, 'id')
            ]);
        }
        
        // Круизы без цен (предупреждение, не ошибка)
        $cruisesWithoutPrices = $this->query("
            SELECT c.id FROM cruises c 
            LEFT JOIN prices p ON c.id = p.cruise_id 
            WHERE p.id IS NULL
        ");
        
        if (count($cruisesWithoutPrices) > 0) {
            $this->addWarning("Найдены круизы без цен", [
                'count' => count($cruisesWithoutPrices),
                'cruise_ids' => array_column($cruisesWithoutPrices, 'id')
            ]);
        }
        
        // Цены без значения
        $pricesWithoutValue = $this->query("
            SELECT id FROM prices 
            WHERE price_value IS NULL OR price_value <= 0
        ");
        
        if (count($pricesWithoutValue) > 0) {
            $this->addError("Найдены цены без значения", [
                'count' => count($pricesWithoutValue)
            ]);
        }
        
        // Проверка валидности JSON в waybill_data
        $cruisesWithInvalidJson = $this->query("
            SELECT id FROM cruises 
            WHERE waybill_data IS NOT NULL 
            AND waybill_data != ''
        ");
        
        foreach ($cruisesWithInvalidJson as $cruise) {
            $cruiseId = $cruise['id'];
            $waybillData = $this->query("SELECT waybill_data FROM cruises WHERE id = ?", [$cruiseId]);
            
            if (!empty($waybillData)) {
                $json = json_decode($waybillData[0]['waybill_data'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->addError("Некорректный JSON в waybill_data для круиза $cruiseId", [
                        'cruise_id' => $cruiseId,
                        'json_error' => json_last_error_msg()
                    ]);
                }
            }
        }
    }
}

