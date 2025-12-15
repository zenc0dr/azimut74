<?php namespace Zen\Worker\Console\infoflot;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Zen\Worker\Classes\ProcessLog;
use Exception;

class InfoflotParse extends Command
{
    protected $name = 'worker:infoflot-parse';
    protected $description = 'Парсинг круизов Infoflot с сохранением в SQLite (Фаза 1)';

    private $timeout = 30;
    private $processed = 0;
    private $errors = 0;
    private $db;
    private $apiKey;

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
        $limit = $this->option('limit');

        // API key: CLI -> .env -> fallback
        $this->apiKey = $this->option('api-key')
            ?: env('INFOFLOT_API_KEY')
            ?: 'b5262f5d8de5be65b201bb5e3f5e544a245b6082';

        if (!$this->apiKey) {
            $this->error('❌ API ключ не указан! Используйте --api-key=YOUR_KEY');
            return 1;
        }

        $this->info('🚢 Начинаем парсинг круизов Infoflot...');
        $this->info("⏱️  Таймаут: {$this->timeout} сек");
        $this->info("🔄 Ограничение времени выполнения: отключено");

        try {
            // Фаза 1: разрешаем создать SQLite при отсутствии файла
            $this->db = new InfoflotDatabase(true);

            // Очистка кеша API (если указан флаг)
            if ($clearCache) {
                $this->info('🧹 Очистка кеша API...');
                $cache = new InfoflotCache();
                $cache->clear();
                $this->info('✅ Кеш очищен');
            }

            if ($clear) {
                $this->info('🧹 Очистка существующих данных...');
                $this->db->clearAll();
                $this->info('✅ Данные очищены');
            }

            $this->info('📥 Скачивание данных с API Infoflot...');
            $this->showProgress('Обработка данных о судах...', 25);

            $dataProcessor = new InfoflotDataProcessor($this->db, $this->apiKey, $this->timeout, $limit);

            $this->showProgress('Обработка данных о судах...', 25);
            $this->processShipsData($dataProcessor);

            $this->showProgress('Обработка круизов и цен...', 75);
            $this->processCruisesData($dataProcessor);

            $this->showProgress('Очистка круизов без цен...', 90);
            $this->cleanCruisesWithoutPrices();

            $this->showProgress('Завершение обработки...', 100);
            $this->line('');

            // Выводим статистику
            $this->displayStats();

            $this->info('✅ Фаза 1 завершена! Данные сохранены в SQLite.');
            $this->info('💡 Для импорта в основную БД используйте Zen\Worker с пулом InfoflotV2');

        } catch (Exception $e) {
            $this->error('❌ Критическая ошибка: ' . $e->getMessage());
            ProcessLog::add('Критическая ошибка парсинга Infoflot: ' . $e->getMessage());
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
    private function processShipsData($dataProcessor)
    {
        $this->info('🚢 Обработка данных о судах...');
        $dataProcessor->processShipsData();
        $this->info('✅ Данные о судах обработаны');
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
        $this->info("🚢 Судов: {$stats['ships']}");
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
            ['api-key', 'k', InputOption::VALUE_REQUIRED, 'API ключ Infoflot'],
        ];
    }
}

