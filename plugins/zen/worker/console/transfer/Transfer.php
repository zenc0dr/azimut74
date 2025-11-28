<?php namespace Zen\Worker\Console\transfer;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Zen\Worker\Classes\ProcessLog;
use Zen\Worker\Console\gama\GamaDatabase;
use Zen\Worker\Console\germes\GermesDatabase;
use Zen\Worker\Console\infoflot\InfoflotDatabase;
use Zen\Worker\Console\volga\VolgaDatabase;
use Zen\Worker\Console\waterway\WaterwayDatabase;
use Exception;

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
     * Execute the console command.
     * @return int
     */
    public function handle()
    {
        // Убираем ограничение времени выполнения
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 0);
        
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
        
        if ($validateOnly) {
            $this->info('🔍 Режим: только валидация');
        } elseif ($skipValidation) {
            $this->info('⚠️  Режим: пропуск валидации');
        } else {
            $this->info('✅ Режим: валидация + импорт');
        }
        
        $this->line('');
        
        // Обрабатываем каждый источник
        foreach ($sourcesToProcess as $sourceKey => $sourceConfig) {
            $this->processSource($sourceKey, $sourceConfig, $validateOnly, $skipValidation);
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
    protected function processSource($sourceKey, $sourceConfig, $validateOnly, $skipValidation)
    {
        $sourceName = $sourceConfig['name'];
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("📦 Обработка источника: $sourceName");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        
        try {
            // Создаем экземпляр Database класса
            $dbClass = $sourceConfig['class'];
            $db = new $dbClass();
            
            // Валидация (если не пропущена)
            if (!$skipValidation) {
                $this->info("🔍 Валидация SQLite базы...");
                $validator = new UnifiedValidator($db, $sourceKey);
                
                if (!$validator->validate()) {
                    $errors = $validator->getErrors();
                    $warnings = $validator->getWarnings();
                    
                    $this->error("❌ Валидация не пройдена для источника: $sourceName");
                    
                    foreach ($errors as $error) {
                        $this->error("  • {$error['message']}");
                        if (!empty($error['context'])) {
                            $context = $error['context'];
                            if (isset($context['count'])) {
                                $this->line("    Количество: {$context['count']}");
                            }
                            if (isset($context['cruise_ids']) && count($context['cruise_ids']) <= 10) {
                                $this->line("    ID круизов: " . implode(', ', $context['cruise_ids']));
                            }
                        }
                    }
                    
                    foreach ($warnings as $warning) {
                        $this->warn("  ⚠️  {$warning['message']}");
                    }
                    
                    $this->stats[$sourceKey] = [
                        'status' => 'validation_failed',
                        'errors' => count($errors),
                        'warnings' => count($warnings)
                    ];
                    
                    return;
                }
                
                $warnings = $validator->getWarnings();
                if (!empty($warnings)) {
                    foreach ($warnings as $warning) {
                        $this->warn("  ⚠️  {$warning['message']}");
                    }
                }
                
                $this->info("✅ Валидация пройдена успешно");
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
                
                $processor->process();
                
                $this->info("✅ Импорт завершен для источника: $sourceName");
                $this->stats[$sourceKey] = [
                    'status' => 'success',
                    'errors' => 0,
                    'warnings' => 0
                ];
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
     * Get the console command options.
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['source', 's', InputOption::VALUE_OPTIONAL, 'Источник для обработки (gama, germes, infoflot, volga, waterway, all)', 'all'],
            ['validate-only', null, InputOption::VALUE_NONE, 'Только валидация без импорта'],
            ['skip-validation', null, InputOption::VALUE_NONE, 'Пропустить валидацию'],
        ];
    }
}

