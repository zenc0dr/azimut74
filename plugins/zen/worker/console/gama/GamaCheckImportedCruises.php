<?php

namespace Zen\Worker\Console\Gama;

use Illuminate\Console\Command;
use Mcmraak\Rivercrs\Models\Checkins;

class GamaCheckImportedCruises extends Command
{
    protected $signature = 'worker:gama-check-imported-cruises';
    protected $description = 'Проверка импортированных круизов Gama';

    public function handle()
    {
        $this->info('🔍 Проверка импортированных круизов Gama...');
        
        $cruises = Checkins::where('eds_code', 'gama')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();
            
        $this->info("📊 Найдено круизов: " . $cruises->count());
        
        foreach ($cruises as $cruise) {
            $this->line("  ID: {$cruise->id}, eds_id: {$cruise->eds_id}, даты: {$cruise->date} - {$cruise->dateb}");
        }
        
        // Проверяем цены для первого круиза
        if ($cruises->count() > 0) {
            $firstCruise = $cruises->first();
            $this->info("\n💰 Проверка цен для круиза ID: {$firstCruise->id}");
            
            $prices = \DB::table('mcmraak_rivercrs_pricing')
                ->where('checkin_id', $firstCruise->id)
                ->get();
                
            $this->info("  Найдено цен: " . $prices->count());
            
            foreach ($prices->take(5) as $price) {
                $this->line("    Цена: {$price->price_a}, {$price->price_b}, каюта: {$price->cabin_id}");
            }
        }
    }
}
