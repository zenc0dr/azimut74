<?php namespace Zen\Worker\Console\volga;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Zen\Worker\Classes\ProcessLog;
use Exception;

class VolgaParse extends Command
{
    protected $name = 'worker:volga-parse';
    protected $description = 'Парсинг круизов Volga с сохранением в SQLite (Фаза 1)';

    private $timeout = 30;
    private $processed = 0;
    private $errors = 0;
    private $db;
    private $nextUrl = 'http://test.volgawolga.ru/xml/daily2024.xml';

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
        $nextUrl = $this->option('next-url');

        // Используем переданный URL или значение по умолчанию
        if ($nextUrl) {
            $this->nextUrl = $nextUrl;
        }

        $this->info('🚢 Начинаем парсинг круизов Volga...');
        $this->info("⏱️  Таймаут: {$this->timeout} сек");
        $this->info("🔄 Ограничение времени выполнения: отключено");
        $this->info("📥 URL источника: {$this->nextUrl}");
        $this->info("📁 XML кеш: storage/parsers_cache/volga/volga_next_url.xml");
        
        try {
            // Фаза 1: разрешаем создать SQLite при отсутствии файла
            $this->db = new VolgaDatabase(true);
            
            if ($clear) {
                $this->info('🧹 Очистка существующих данных...');
                $this->db->clearAll();
                $this->info('✅ Данные очищены');
            }
            
            $this->info('📥 Скачивание XML файла...');
            $this->showProgress('Скачивание XML...', 10);
            
            $apiClient = new VolgaApiClient($this->nextUrl, $this->timeout);
            if ($clearCache) {
                $this->info('🧹 Очистка XML кеша...');
                $apiClient->clearCache();
                $this->info('✅ XML кеш очищен');
            }
            // Кеш по умолчанию: если XML уже есть, скачивание пропускается
            $apiClient->downloadXmlFile((bool)$clearCache);
            
            $this->showProgress('Парсинг XML...', 20);
            $dump = $apiClient->getXmlData();
            
            $this->showProgress('Обработка данных...', 30);
            $dataProcessor = new VolgaDataProcessor($this->db, $this->timeout, $limit);
            $dataProcessor->processAllData($dump);
            
            $this->showProgress('Очистка круизов без цен...', 90);
            $this->cleanCruisesWithoutPrices();
            
            $this->showProgress('Завершение обработки...', 100);
            $this->line('');
            
            // Выводим статистику
            $this->displayStats();
            
            $this->info('✅ Фаза 1 завершена! Данные сохранены в SQLite.');
            $this->info('💡 Для импорта в основную БД используйте Zen\Worker с пулом VolgaV2');
            
        } catch (Exception $e) {
            $this->error('❌ Критическая ошибка: ' . $e->getMessage());
            ProcessLog::add('Критическая ошибка парсинга Volga: ' . $e->getMessage());
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
     * Отображение статистики
     */
    private function displayStats()
    {
        $stats = $this->db->getStats();
        
        $this->info('📈 === СТАТИСТИКА ФАЗЫ 1 ===');
        $this->info("🚢 Теплоходов: {$stats['ships']}");
        $this->info("🏗️  Палуб: {$stats['decks']}");
        $this->info("🎫 Круизов: {$stats['cruises']}");
        $this->info("🏠 Категорий кают: {$stats['cabin_categories']}");
        $this->info("🚪 Кают: {$stats['cabins']}");
        $this->info("💰 Цен: {$stats['prices']}");
        $this->info("🗺️  Путевых листов: {$stats['waybills']}");
        $this->info("💾 База данных: " . $this->db->getDbPath());
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
        return [
            ['timeout', 't', InputOption::VALUE_OPTIONAL, 'Таймаут для HTTP запросов в секундах', 30],
            ['clear', 'c', InputOption::VALUE_NONE, 'Очистить существующие данные перед парсингом'],
            ['clear_cache', null, InputOption::VALUE_NONE, 'Очистить кеш XML Volga перед парсингом'],
            ['limit', 'l', InputOption::VALUE_OPTIONAL, 'Ограничить количество записей для тестирования', null],
            ['next-url', 'u', InputOption::VALUE_OPTIONAL, 'URL источника XML данных', 'http://test.volgawolga.ru/xml/daily2024.xml'],
        ];
    }
}

