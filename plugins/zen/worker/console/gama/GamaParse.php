<?php namespace Zen\Worker\Console\gama;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Zen\Worker\Classes\ProcessLog;
use Exception;

class GamaParse extends Command
{
    protected $name = 'worker:gama-parse';
    protected $description = 'Парсинг круизов Gama с сохранением в SQLite';

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
        $phase2 = $this->option('phase2');
        $importOnly = $this->option('import-only');
        $limit = $this->option('limit');
        
        $this->info('🚢 Начинаем парсинг круизов Gama...');
        $this->info("⏱️  Таймаут: {$this->timeout} сек");
        $this->info("🔄 Ограничение времени выполнения: отключено");
        
        try {
            $this->db = new GamaDatabase();
            
            // Если запущена только фаза 2
            if ($phase2) {
                $this->runPhase2();
                return 0;
            }

            // Если запущен только импорт
            if ($importOnly) {
                $this->runImportOnly();
                return 0;
            }
            
            if ($clear) {
                $this->info('🧹 Очистка существующих данных...');
                $this->db->clearAll();
                $this->info('✅ Данные очищены');
            }
            
            $this->info('📥 Скачивание данных с API Gama...');
            $this->showProgress('Скачивание архива...', 0);
            
            $dataProcessor = new GamaDataProcessor($this->db, $this->timeout, $limit);
            
            $this->showProgress('Обработка навигационных данных...', 25);
            $this->processNavigationData($dataProcessor);
            
            $this->showProgress('Обработка данных о теплоходах...', 50);
            $this->processShipsData($dataProcessor);
            
            $this->showProgress('Обработка круизов и цен...', 75);
            $this->processCruisesData($dataProcessor);
            
            $this->showProgress('Очистка круизов без цен...', 90);
            $this->cleanCruisesWithoutPrices();
            
            $this->showProgress('Завершение обработки...', 100);
            $this->line('');
            
            // Выводим статистику
            $this->displayStats();
            
            // По умолчанию запускаем фазу 2, но можно отключить флагом --no-phase2
            if (!$this->option('no-phase2')) {
                $this->info('🔄 Запуск фазы 2 (импорт в основную БД)...');
                $this->runPhase2();
            }
            
        } catch (Exception $e) {
            $this->error('❌ Критическая ошибка: ' . $e->getMessage());
            ProcessLog::add('Критическая ошибка парсинга Gama: ' . $e->getMessage());
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
     * Обработка навигационных данных
     */
    private function processNavigationData($dataProcessor)
    {
        $this->info('📊 Обработка навигационных данных...');
        $dataProcessor->processNavigationData();
        $this->info('✅ Навигационные данные обработаны');
    }

    /**
     * Обработка данных о теплоходах
     */
    private function processShipsData($dataProcessor)
    {
        $this->info('🚢 Обработка данных о теплоходах...');
        $dataProcessor->processShipsData();
        $this->info('✅ Данные о теплоходах обработаны');
    }

    /**
     * Обработка круизов и цен
     */
    private function processCruisesData($dataProcessor)
    {
        $this->info('🎫 Обработка круизов и цен...');
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
        $this->info("🏠 Категорий кают: {$stats['cabin_categories']}");
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
     * Get the console command options.
     * @return array
     */
    /**
     * Запуск фазы 2 - импорт из SQLite в основную БД
     */
    private function runPhase2()
    {
        $this->info('🔄 Фаза 2: Импорт данных из SQLite в основную БД...');
        
        try {
            $importer = new GamaMainDbImporter($this->db);
            $limit = $this->option('limit');
            if ($limit) {
                $importer->setLimit((int)$limit);
            }
            $stats = $importer->importAll();
            
            $this->info('✅ Фаза 2 завершена!');
            $this->displayImportStats($stats);
            
        } catch (Exception $e) {
            $this->error('❌ Ошибка фазы 2: ' . $e->getMessage());
            ProcessLog::add('Ошибка фазы 2: ' . $e->getMessage());
        }
    }

    /**
     * Запуск только импорта (без парсинга)
     */
    private function runImportOnly()
    {
        $this->info('📥 Импорт данных из SQLite в основную БД...');
        
        try {
            $importer = new GamaMainDbImporter($this->db);
            $limit = $this->option('limit');
            if ($limit) {
                $importer->setLimit((int)$limit);
            }
            $stats = $importer->importAll();
            
            $this->info('✅ Импорт завершен!');
            $this->displayImportStats($stats);
            
        } catch (Exception $e) {
            $this->error('❌ Ошибка импорта: ' . $e->getMessage());
            ProcessLog::add('Ошибка импорта: ' . $e->getMessage());
        }
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
     * Отображение статистики импорта
     */
    private function displayImportStats($stats)
    {
        $this->info("\n=== СТАТИСТИКА ИМПОРТА ===");
        $this->info("  Теплоходов импортировано: {$stats['ships']}");
        $this->info("  Категорий кают импортировано: {$stats['cabins']}");
        $this->info("  Круизов импортировано: {$stats['cruises']}");
        $this->info("  Цен импортировано: {$stats['prices']}");
        $this->info("  Путевых листов импортировано: {$stats['waybills']}");
        $this->info("  Ошибок: {$stats['errors']}");
    }

    protected function getOptions()
    {
        return [
            ['timeout', 't', InputOption::VALUE_OPTIONAL, 'Таймаут для HTTP запросов в секундах', 30],
            ['clear', 'c', InputOption::VALUE_NONE, 'Очистить существующие данные перед парсингом'],
            ['phase2', 'p', InputOption::VALUE_NONE, 'Запустить только фазу 2 (импорт из SQLite в основную БД)'],
            ['import-only', 'i', InputOption::VALUE_NONE, 'Импортировать только новые данные (без парсинга)'],
            ['limit', 'l', InputOption::VALUE_OPTIONAL, 'Ограничить количество записей для тестирования', null],
            ['no-phase2', null, InputOption::VALUE_NONE, 'Не запускать фазу 2 автоматически после парсинга'],
        ];
    }
}