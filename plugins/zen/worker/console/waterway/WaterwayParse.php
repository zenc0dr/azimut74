<?php namespace Zen\Worker\Console\waterway;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Zen\Worker\Classes\ProcessLog;
use Exception;

class WaterwayParse extends Command
{
    protected $name = 'worker:waterway-parse';
    protected $description = 'Парсинг круизов Waterway с сохранением в SQLite (Фаза 1)';

    private $timeout = 30;
    private $processed = 0;
    private $errors = 0;
    private $db;

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        // Убираем ограничение времени выполнения для консольного скрипта
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 0);
        ini_set('max_input_time', -1);
        
        // Дополнительные настройки для консольного скрипта
        if (function_exists('ignore_user_abort')) {
            ignore_user_abort(true);
        }
        
        $this->timeout = $this->option('timeout');
        $clear = $this->option('clear');
        $clearCache = $this->option('clear_cache');
        // legacy
        $limit = $this->option('limit');
        // new granular limits (safe debug / cache warm-up)
        $limitShips = $this->option('limit_ships');
        $limitCruises = $this->option('limit_cruises');
        $limitCruisesPerShip = $this->option('limit_cruises_per_ship');
        $progressEvery = $this->option('progress_every');
        $onlyCruiseId = $this->option('only_cruise_id');
        
        $this->info('🚢 Начинаем парсинг круизов Waterway...');
        $this->info("⏱️  Таймаут: {$this->timeout} сек");
        $this->info("🔄 Ограничение времени выполнения: отключено");
        if ($limitShips) $this->info("🧪 Лимит теплоходов: {$limitShips}");
        if ($limitCruisesPerShip) $this->info("🧪 Лимит круизов на теплоход: {$limitCruisesPerShip}");
        if ($limitCruises) $this->info("🧪 Лимит круизов: {$limitCruises}");
        if ($limit) $this->info("🧪 Лимит круизов (legacy --limit): {$limit}");
        
        try {
            // Фаза 1: разрешаем создать SQLite при отсутствии файла
            $this->db = new WaterwayDatabase(true);
            
            // Очистка кеша API (если указан флаг)
            if ($clearCache) {
                $this->info('🧹 Очистка кеша API...');
                $cache = new WaterwayCache();
                $cache->clear();
                $this->info('✅ Кеш очищен');
            }
            
            if ($clear) {
                $this->info('🧹 Очистка существующих данных...');
                $this->db->clearAll();
                $this->info('✅ Данные очищены');
            }
            
            $this->info('📥 Получение данных с API Waterway...');
            $this->showProgress('Обработка данных о теплоходах...', 10);
            
            $dataProcessor = new WaterwayDataProcessor(
                $this->db,
                $this->timeout,
                $limit,
                $limitShips,
                $limitCruises,
                $limitCruisesPerShip
            );
            $dataProcessor
                ->setCommand($this)
                ->setProgressEvery((int)$progressEvery)
                ->setOnlyCruiseId($onlyCruiseId ? (int)$onlyCruiseId : null);
            
            $this->showProgress('Обработка данных о теплоходах...', 25);
            $this->processMotorshipsData($dataProcessor);
            
            $this->showProgress('Обработка круизов, расписаний и цен...', 50);
            $this->processCruisesData($dataProcessor);
            
            $this->showProgress('Очистка круизов без цен...', 90);
            $this->cleanCruisesWithoutPrices();
            
            $this->showProgress('Завершение обработки...', 100);
            $this->line('');
            
            // Выводим статистику
            $this->displayStats();
            
            $this->info('✅ Фаза 1 завершена! Данные сохранены в SQLite.');
            $this->info('💡 Для импорта в основную БД используйте Zen\Worker с пулом WaterwayV2');
            
        } catch (Exception $e) {
            $this->error('❌ Критическая ошибка: ' . $e->getMessage());
            ProcessLog::add('Критическая ошибка парсинга Waterway: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }

    /**
     * Показ прогресса с анимацией
     */
    private function showProgress($message, $percent)
    {
        $this->info("🔄 $message");
        
        // Создаем анимацию загрузки
        $spinner = ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'];
        $spinnerIndex = 0;
        
        for ($i = 0; $i < 10; $i++) {
            $this->output->write("\r" . $spinner[$spinnerIndex] . " " . $message . " (" . $percent . "%)");
            $spinnerIndex = ($spinnerIndex + 1) % count($spinner);
            usleep(100000); // 0.1 секунды
        }
        
        $this->line('');
    }

    /**
     * Обработка данных о теплоходах
     */
    private function processMotorshipsData($dataProcessor)
    {
        $this->info('🚢 Обработка данных о теплоходах...');
        $dataProcessor->processMotorshipsData();
        $this->info('✅ Данные о теплоходах обработаны');
    }

    /**
     * Обработка круизов и цен
     */
    private function processCruisesData($dataProcessor)
    {
        $this->info('🎫 Обработка круизов, расписаний и цен...');
        $dataProcessor->processCruisesData();
        $this->info('✅ Обработка круизов завершена');
    }

    /**
     * Отображение статистики
     */
    private function displayStats()
    {
        $stats = $this->db->getStats();
        
        $this->info('📈 === СТАТИСТИКА ФАЗЫ 1 ===');
        $this->info("🚢 Теплоходов: {$stats['ships']}");
        $this->info("🎫 Круизов: {$stats['cruises']}");
        $this->info("💰 Цен: {$stats['prices']}");
        $this->info("💾 База данных: " . $this->db->getDbPath());
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
     * Очистка круизов без цен (конец фазы 1)
     */
    private function cleanCruisesWithoutPrices()
    {
        $this->info('🧹 Очистка круизов без цен...');
        
        try {
            $result = $this->db->cleanCruisesWithoutPrices();
            
            $this->info("✅ Очистка завершена:");
            $this->info("  Всего круизов: {$result['total']}");
            $this->info("  Удалено без цен: {$result['deleted']}");
            $this->info("  Осталось с ценами: {$result['remaining']}");
            
        } catch (Exception $e) {
            $this->error('❌ Ошибка при очистке: ' . $e->getMessage());
            ProcessLog::add('Ошибка при очистке круизов без цен: ' . $e->getMessage());
        }
    }

    /**
     * Get the console command options.
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['timeout', 't', InputOption::VALUE_OPTIONAL, 'Таймаут для HTTP запросов в секундах', 30],
            ['clear', 'c', InputOption::VALUE_NONE, 'Очистить существующие данные перед парсингом'],
            ['clear_cache', null, InputOption::VALUE_NONE, 'Очистить кеш API перед парсингом'],
            ['limit', 'l', InputOption::VALUE_OPTIONAL, 'Ограничить количество записей для тестирования', null],
            ['limit_ships', null, InputOption::VALUE_OPTIONAL, 'Ограничить количество теплоходов (для прогрева кеша/отладки)', null],
            ['limit_cruises', null, InputOption::VALUE_OPTIONAL, 'Ограничить количество круизов (для прогрева кеша/отладки)', null],
            ['limit_cruises_per_ship', null, InputOption::VALUE_OPTIONAL, 'Ограничить количество круизов на один теплоход', null],
            ['progress_every', null, InputOption::VALUE_OPTIONAL, 'Выводить прогресс каждые N круизов (1 = каждый круиз)', 1],
            ['only_cruise_id', null, InputOption::VALUE_OPTIONAL, 'Обработать только один круиз по ID (точечный режим)', null],
        ];
    }
}

