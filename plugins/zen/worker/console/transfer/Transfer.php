<?php namespace Zen\Worker\Console\transfer;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Zen\Worker\Classes\ProcessLog;
use Zen\Worker\Console\gama\GamaDatabase;
use Zen\Worker\Console\germes\GermesDatabase;
use Zen\Worker\Console\infoflot\InfoflotDatabase;
use Zen\Worker\Console\volga\VolgaDatabase;
use Zen\Worker\Console\waterway\WaterwayDatabase;
use Zen\Worker\Console\transfer\TelegramNotifier;
use Zen\Worker\Console\transfer\TransferConfig;
use Zen\Worker\Console\transfer\TransferErrorLogger;
use Exception;
use DB;

/**
 * Команда для импорта данных из SQLite баз в MySQL
 */
class Transfer extends Command
{
    protected $name = 'worker:transfer';
    protected $description = 'Импорт данных из SQLite баз в MySQL (Фаза 2)';
    
    /**
     * Конфигурация источников
     */
    protected $sources = [
        'gama' => [
            'class' => GamaDatabase::class,
            'edsCode' => 'gama',
            'edsIdField' => 'gama_id',
            'name' => 'Gama'
        ],
        'germes' => [
            'class' => GermesDatabase::class,
            'edsCode' => 'germes',
            'edsIdField' => 'germes_id',
            'name' => 'Germes'
        ],
        'infoflot' => [
            'class' => InfoflotDatabase::class,
            'edsCode' => 'infoflot',
            'edsIdField' => 'infoflot_id',
            'name' => 'Infoflot'
        ],
        'volga' => [
            'class' => VolgaDatabase::class,
            'edsCode' => 'volga',
            'edsIdField' => 'volga_id',
            'name' => 'Volga'
        ],
        'waterway' => [
            'class' => WaterwayDatabase::class,
            'edsCode' => 'waterway',
            'edsIdField' => 'waterway_id',
            'name' => 'Waterway'
        ],
    ];
    
    /**
     * Статистика обработки
     */
    protected $stats = [];
    
    /**
     * Telegram уведомления
     */
    protected $telegram;
    
    /**
     * Логгер ошибок валидации
     */
    protected $errorLogger;
    
    /**
     * Execute the console command.
     * @return int
     */
    public function handle()
    {
        // Убираем ограничение времени выполнения
        set_time_limit(0);
        // Как в worker:*-parse: не ниже 1536M — иначе после фазы 1 в том же PHP-процессе (infoflot-sync)
        // остаётся ~700MB+ и ini_set('512M') падает: «Failed to set memory limit... Current memory usage is ...»
        ini_set('memory_limit', '1536M');
        ini_set('max_execution_time', 0);
        
        // Инициализируем логгер ошибок и очищаем лог при запуске
        $this->errorLogger = new TransferErrorLogger();
        $this->errorLogger->clearLog();
        
        $noTelegram = (bool)$this->option('no-telegram');

        // Инициализируем Telegram уведомления (если не отключены)
        if (!$noTelegram) {
            $this->telegram = new TelegramNotifier();
            $this->telegram->reset();
        } else {
            $this->telegram = null;
        }
        
        $source = $this->option('source');
        $validateOnly = $this->option('validate-only');
        $skipValidation = $this->option('skip-validation');
        
        $this->info('🔄 Начинаем импорт данных из SQLite баз в MySQL...');
        
        // Определяем список источников для обработки
        $sourcesToProcess = $this->getSourcesToProcess($source);
        
        if (empty($sourcesToProcess)) {
            $this->error('❌ Не указаны источники для обработки');
            return 1;
        }
        
        $this->info('📋 Источники для обработки: ' . implode(', ', array_keys($sourcesToProcess)));
        
        // Проверка существования баз данных перед началом обработки
        $this->info('🔍 Проверка наличия баз данных...');
        $missingDatabases = [];
        foreach ($sourcesToProcess as $sourceKey => $sourceConfig) {
            try {
                $dbPath = TransferConfig::getDbPath($sourceKey);
                if (!file_exists($dbPath)) {
                    $missingDatabases[] = [
                        'source' => $sourceKey,
                        'path' => $dbPath
                    ];
                } else {
                    $this->info("  ✅ {$sourceConfig['name']}: " . basename($dbPath));
                }
            } catch (Exception $e) {
                $missingDatabases[] = [
                    'source' => $sourceKey,
                    'path' => 'не найден',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        if (!empty($missingDatabases)) {
            $this->error('❌ Не найдены базы данных для следующих источников:');
            foreach ($missingDatabases as $missing) {
                $this->error("  • {$missing['source']}: {$missing['path']}");
                if (isset($missing['error'])) {
                    $this->error("    Ошибка: {$missing['error']}");
                }
            }
            return 1;
        }
        
        if ($validateOnly) {
            $this->info('🔍 Режим: только валидация');
        } elseif ($skipValidation) {
            $this->info('⚠️  Режим: пропуск валидации');
        } else {
            $this->info('✅ Режим: валидация + импорт');
        }
        
        $this->line('');
        
        // Инициализируем данные о всех источниках для Telegram
        $sourcesData = [];
        foreach ($sourcesToProcess as $sourceKey => $sourceConfig) {
            $sourcesData[$sourceKey] = [
                'name' => $sourceConfig['name'],
                'status' => 'pending',
                'stats' => []
            ];
        }
        
        // Отправляем начальное сообщение в Telegram
        if ($this->telegram) {
            $this->telegram->updateProgress($sourcesData);
        }
        
        // Обрабатываем каждый источник
        foreach ($sourcesToProcess as $sourceKey => $sourceConfig) {
            $this->processSource($sourceKey, $sourceConfig, $validateOnly, $skipValidation, $sourcesData);
        }
        
        // Финальное обновление сообщения
        if ($this->telegram) {
            $this->telegram->updateProgress($sourcesData);
        }
        
        // Выводим итоговую статистику
        $this->displaySummary();
        
        return 0;
    }
    
    /**
     * Получение списка источников для обработки
     */
    protected function getSourcesToProcess($source)
    {
        if ($source === 'all' || empty($source)) {
            return $this->sources;
        }
        
        if (isset($this->sources[$source])) {
            return [$source => $this->sources[$source]];
        }
        
        $this->error("❌ Неизвестный источник: $source");
        $this->info('Доступные источники: ' . implode(', ', array_keys($this->sources)) . ', all');
        return [];
    }
    
    /**
     * Обработка одного источника
     */
    protected function processSource($sourceKey, $sourceConfig, $validateOnly, $skipValidation, &$sourcesData = [])
    {
        $sourceName = $sourceConfig['name'];
        $edsCode = $sourceConfig['edsCode'];
        
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("📦 Обработка источника: $sourceName");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        
        // Обновляем статус источника в данных для Telegram
        if (isset($sourcesData[$sourceKey])) {
            $sourcesData[$sourceKey]['status'] = 'processing';
            if ($this->telegram) {
                $this->telegram->updateProgress($sourcesData, $sourceKey);
            }
        }
        
        try {
            // Создаем экземпляр Database класса
            $dbClass = $sourceConfig['class'];
            $db = new $dbClass();
            
            // Валидация (если не пропущена)
            if (!$skipValidation) {
                $this->info("🔍 Валидация SQLite базы...");
                $validator = new UnifiedValidator($db, $sourceKey);
                
                // Выполняем валидацию (не блокируем импорт при ошибках)
                $validator->validate();
                $errors = $validator->getErrors();
                $warnings = $validator->getWarnings();
                
                // Логируем все ошибки и предупреждения в файл
                if (!empty($errors) || !empty($warnings)) {
                    $this->errorLogger->logErrors($sourceKey, $errors, $warnings);
                    
                    $totalIssues = count($errors) + count($warnings);
                    $logPath = $this->errorLogger->getLogPath();
                    
                    $this->warn("⚠️  Найдены проблемы целостности данных для источника: $sourceName");
                    $this->warn("   Ошибок: " . count($errors) . ", Предупреждений: " . count($warnings));
                    $this->info("   📝 Все проблемы записаны в лог: $logPath");
                    $this->info("   💡 Импорт продолжается. Ошибки будут исправлены инженером парсеров на фазе 1.");
                } else {
                    $this->info("✅ Валидация пройдена успешно");
                }
            }
            
            // Импорт (если не только валидация)
            if (!$validateOnly) {
                $this->info("📥 Импорт данных в MySQL...");
                
                $processor = new UnifiedProcessor(
                    $db,
                    $sourceKey,
                    $sourceConfig['edsCode'],
                    $sourceConfig['edsIdField']
                );
                
                // Передаем команду для вывода в консоль
                $processor->setCommand($this);

                $handleOnly = $this->option('handle_only');
                if ($handleOnly !== null && $handleOnly !== '' && $sourceKey === 'waterway') {
                    $processor->setHandleOnlyCruiseId((int) $handleOnly);
                }

                $processor->process();
                
                // Получаем статистику обработанных записей
                $stats = $this->getProcessedStats($edsCode);
                
                $this->info("✅ Импорт завершен для источника: $sourceName");
                $this->info("📊 Статистика:");
                $this->info("  🚢 Обработано теплоходов: {$stats['ships']}");
                $this->info("  🏠 Обработано категорий кают: {$stats['cabin_categories']}");
                $this->info("  🎫 Обработано круизов: {$stats['cruises']}");
                
                $this->stats[$sourceKey] = [
                    'status' => 'success',
                    'errors' => 0,
                    'warnings' => 0,
                    'stats' => $stats
                ];
                
                // Обновляем статус в Telegram
                if (isset($sourcesData[$sourceKey])) {
                    $sourcesData[$sourceKey]['status'] = 'success';
                    $sourcesData[$sourceKey]['stats'] = $stats;
                    if ($this->telegram) {
                        $this->telegram->updateProgress($sourcesData);
                    }
                }
            } else {
                $this->info("✅ Валидация завершена для источника: $sourceName");
                $this->stats[$sourceKey] = [
                    'status' => 'validated',
                    'errors' => 0,
                    'warnings' => 0
                ];
            }
            
        } catch (Exception $e) {
            $this->error("❌ Ошибка обработки источника $sourceName: " . $e->getMessage());
            ProcessLog::add("Критическая ошибка обработки источника $sourceName: " . $e->getMessage());
            
            $this->stats[$sourceKey] = [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
            
            // Обновляем статус в Telegram
            if (isset($sourcesData[$sourceKey])) {
                $sourcesData[$sourceKey]['status'] = 'error';
                $sourcesData[$sourceKey]['error'] = $e->getMessage();
                if ($this->telegram) {
                    $this->telegram->updateProgress($sourcesData);
                }
            }
        }
        
        $this->line('');
    }
    
    /**
     * Вывод итоговой статистики
     */
    protected function displaySummary()
    {
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("📊 Итоговая статистика");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        
        $successCount = 0;
        $errorCount = 0;
        $validatedCount = 0;
        
        foreach ($this->stats as $sourceKey => $stat) {
            $sourceName = $this->sources[$sourceKey]['name'];
            
            switch ($stat['status']) {
                case 'success':
                    $this->info("✅ $sourceName: успешно");
                    $successCount++;
                    break;
                case 'validated':
                    $this->info("🔍 $sourceName: валидация пройдена");
                    $validatedCount++;
                    break;
                case 'validation_failed':
                    $this->error("❌ $sourceName: валидация не пройдена ({$stat['errors']} ошибок)");
                    $errorCount++;
                    break;
                case 'error':
                    $this->error("❌ $sourceName: ошибка обработки");
                    $errorCount++;
                    break;
            }
        }
        
        $this->line('');
        $this->info("Всего обработано: " . count($this->stats));
        $this->info("Успешно: $successCount");
        $this->info("Валидация: $validatedCount");
        $this->info("Ошибок: $errorCount");
    }
    
    /**
     * Получение статистики обработанных записей для источника
     * 
     * @param string $edsCode EDS код источника
     * @return array Статистика (ships, cabin_categories, cruises)
     */
    protected function getProcessedStats($edsCode)
    {
        return [
            'ships' => $this->countMotorships($edsCode),
            'cabin_categories' => $this->countCabinCategories($edsCode),
            'cruises' => $this->countCruises($edsCode)
        ];
    }
    
    /**
     * Подсчет количества обработанных теплоходов для источника
     * Считаем теплоходы, у которых заполнено поле {eds_code}_id
     * 
     * @param string $edsCode EDS код источника
     * @return int
     */
    protected function countMotorships($edsCode)
    {
        $edsIdField = $edsCode . '_id';
        return DB::table('mcmraak_rivercrs_motorships')
            ->where($edsIdField, '>', 0)
            ->count();
    }
    
    /**
     * Подсчет количества обработанных категорий кают для источника
     * Считаем категории кают, у которых заполнено поле {eds_code}_name
     * 
     * @param string $edsCode EDS код источника
     * @return int
     */
    protected function countCabinCategories($edsCode)
    {
        $edsNameField = $edsCode . '_name';
        return DB::table('mcmraak_rivercrs_cabins')
            ->whereNotNull($edsNameField)
            ->where($edsNameField, '!=', '')
            ->count();
    }
    
    /**
     * Подсчет количества обработанных круизов для источника
     * 
     * @param string $edsCode EDS код источника
     * @return int
     */
    protected function countCruises($edsCode)
    {
        return DB::table('mcmraak_rivercrs_checkins')
            ->where('eds_code', $edsCode)
            ->count();
    }
    
    /**
     * Get the console command options.
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['source', 's', InputOption::VALUE_OPTIONAL, 'Источник для обработки (gama, germes, infoflot, volga, waterway, all)', 'all'],
            ['validate-only', null, InputOption::VALUE_NONE, 'Только валидация без импорта'],
            ['skip-validation', null, InputOption::VALUE_NONE, 'Пропустить валидацию'],
            ['no-telegram', null, InputOption::VALUE_NONE, 'Отключить Telegram-уведомления (для внешних оркестраторов)'],
            ['handle_only', null, InputOption::VALUE_OPTIONAL, 'Waterway: импортировать только круиз с этим id в SQLite (= eds_id)', null],
        ];
    }
}

