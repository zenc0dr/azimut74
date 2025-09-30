<?php namespace Zen\Worker\Console\gama;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class GamaViewer extends Command
{
    protected $name = 'worker:gama-view';
    protected $description = 'Просмотр данных Gama из SQLite базы';

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        $db = new GamaDatabase();
        
        $this->info('=== ПРОСМОТР ДАННЫХ GAMA ===');
        $this->line('');
        
        // Показываем статистику
        $stats = $db->getStats();
        $this->info('СТАТИСТИКА:');
        $this->info("  Теплоходов: {$stats['ships']}");
        $this->info("  Круизов: {$stats['cruises']}");
        $this->info("  Категорий кают: {$stats['cabin_categories']}");
        $this->info("  Цен: {$stats['prices']}");
        $this->line('');
        
        // Показываем теплоходы
        $this->showShips($db);
        
        // Показываем круизы
        $this->showCruises($db);
        
        $this->info("База данных: " . $db->getDbPath());
    }

    /**
     * Показ теплоходов
     */
    private function showShips($db)
    {
        $this->info('ТЕПЛОХОДЫ:');
        
        $stmt = $db->getPdo()->query("
            SELECT gama_ship_id, name, created_at 
            FROM ships 
            ORDER BY name 
            LIMIT 10
        ");
        
        $ships = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        foreach ($ships as $ship) {
            $this->info("  ID: {$ship['gama_ship_id']} - {$ship['name']}");
        }
        
        if (count($ships) == 10) {
            $this->info("  ... и еще теплоходы");
        }
        
        $this->line('');
    }

    /**
     * Показ круизов
     */
    private function showCruises($db)
    {
        $this->info('КРУИЗЫ:');
        
        $cruises = $db->getAllCruises();
        $sampleCruises = array_slice($cruises, 0, 5);
        
        foreach ($sampleCruises as $cruise) {
            $this->info("  ID: {$cruise['gama_cruise_id']} - {$cruise['name']}");
            $this->info("    Теплоход: {$cruise['ship_name']}");
            $this->info("    Даты: {$cruise['date_start']} - {$cruise['date_end']}");
            
            // Показываем цены
            $prices = $db->getCruisePrices($cruise['id']);
            if (!empty($prices)) {
                $this->info("    Цены:");
                foreach (array_slice($prices, 0, 3) as $price) {
                    $this->info("      {$price['category_name']} ({$price['places']} мест): {$price['price_1']} руб.");
                }
                if (count($prices) > 3) {
                    $this->info("      ... и еще " . (count($prices) - 3) . " цен");
                }
            }
            $this->line('');
        }
        
        if (count($cruises) > 5) {
            $this->info("  ... и еще " . (count($cruises) - 5) . " круизов");
        }
    }

    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     * @return array
     */
    protected function getOptions()
    {
        return [];
    }
}
