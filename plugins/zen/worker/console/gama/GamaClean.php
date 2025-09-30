<?php

namespace Zen\Worker\Console\gama;

use Illuminate\Console\Command;
use DB;

class GamaClean extends Command
{
    protected $signature = 'worker:gama-clean';
    protected $description = 'Очистка старых данных Gama';

    public function handle()
    {
        $this->info('🧹 Очистка старых данных Gama...');
        
        // Удаляем старые цены
        $deletedPrices = DB::table('mcmraak_rivercrs_pricing')
            ->whereIn('checkin_id', function($query) {
                $query->select('id')
                      ->from('mcmraak_rivercrs_checkins')
                      ->where('eds_code', 'gama');
            })
            ->delete();
        
        $this->info("✅ Удалено цен: $deletedPrices");
        
        // Удаляем старые заезды
        $deletedCheckins = DB::table('mcmraak_rivercrs_checkins')
            ->where('eds_code', 'gama')
            ->delete();
        
        $this->info("✅ Удалено заездов: $deletedCheckins");
        
        // Удаляем SQLite файл
        $sqlitePath = storage_path('gama_data.sqlite');
        if (file_exists($sqlitePath)) {
            unlink($sqlitePath);
            $this->info("✅ SQLite файл удален");
        }
        
        $this->info("🎉 Очистка завершена!");
    }
}
