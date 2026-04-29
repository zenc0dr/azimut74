<?php namespace Zen\Worker\Console\sync;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Zen\Worker\Classes\WorkerNotifier;

/**
 * GermesSync
 *
 * Единая команда для Germes:
 * - Фаза 1: worker:germes-parse (API -> SQLite, кеш вечный)
 * - Фаза 2: worker:transfer --source=germes (SQLite -> MySQL)
 *
 * По умолчанию работает в validate-first:
 * - без --import выполняет только валидацию фазы 2
 * - с --import делает 2 шага: validate-only -> import(--skip-validation)
 */
class GermesSync extends Command
{
    protected $name = 'worker:germes-sync';
    protected $description = 'Germes: parse (SQLite) + transfer (MySQL)';

    public function handle()
    {
        $label = 'Germes';

        $parseOnly = (bool)$this->option('parse-only');
        $transferOnly = (bool)$this->option('transfer-only');
        $doImport = (bool)$this->option('import');
        $validateOnly = (bool)$this->option('validate-only');
        $skipValidation = (bool)$this->option('skip-validation');

        if ($parseOnly && $transferOnly) {
            $this->error('Нельзя одновременно указать --parse-only и --transfer-only');
            return 1;
        }

        if ($doImport && $validateOnly) {
            $this->error('Нельзя одновременно указать --import и --validate-only');
            return 1;
        }

        // --- Phase 1 args (germes-parse) ---
        $parseArgs = [
            '--timeout' => $this->option('timeout'),
        ];

        if ($this->option('clear')) {
            $parseArgs['--clear'] = true;
        }
        if ($this->option('clear_cache')) {
            $parseArgs['--clear_cache'] = true;
        }
        if ($this->option('limit')) {
            $parseArgs['--limit'] = $this->option('limit');
        }

        // --- Phase 2 args (transfer) ---
        $transferArgs = [
            '--source' => 'germes',
        ];

        if (!$doImport) {
            $transferArgs['--validate-only'] = true;
        } elseif ($skipValidation) {
            $transferArgs['--skip-validation'] = true;
        }

        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('⛴ Germes sync');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('Важно: кеш Germes вечный (storage/parsers_cache/germes) и не очищается без --clear_cache');

        WorkerNotifier::notify("⛴ {$label} sync: старт");

        // Phase 1
        if (!$transferOnly) {
            $this->line('');
            $this->info('🟦 Фаза 1: API -> SQLite (worker:germes-parse)');
            WorkerNotifier::notify("⛴ {$label}: фаза 1 (парсинг в SQLite) — начало");

            $code = $this->call('worker:germes-parse', $parseArgs);
            if ($code !== 0) {
                $this->error("Фаза 1 завершилась с кодом $code. Останавливаемся.");
                WorkerNotifier::notify("⛴ {$label}: фаза 1 — ошибка (код выхода $code)");
                return $code;
            }

            WorkerNotifier::notify("⛴ {$label}: фаза 1 — завершена");
        }

        // Phase 2
        if (!$parseOnly) {
            $this->line('');
            $this->info('🟩 Фаза 2: SQLite -> MySQL (worker:transfer --source=germes)');
            WorkerNotifier::notify("⛴ {$label}: фаза 2 (перенос в MySQL) — начало");

            $transferArgs['--no-telegram'] = true;

            if ($doImport && !$skipValidation) {
                $this->info('🔍 Фаза 2.1: валидация SQLite (transfer --validate-only)');

                $code = $this->call('worker:transfer', [
                    '--source' => 'germes',
                    '--validate-only' => true,
                    '--no-telegram' => true,
                ]);
                if ($code !== 0) {
                    $this->error("Фаза 2.1 (валидация) завершилась с кодом $code.");
                    WorkerNotifier::notify("⛴ {$label}: фаза 2 — ошибка на валидации (код $code)");
                    return $code;
                }

                $this->info('✅ Фаза 2.1: валидация пройдена, начинаю импорт');

                $this->info('📥 Фаза 2.2: импорт (transfer --skip-validation)');

                $code = $this->call('worker:transfer', [
                    '--source' => 'germes',
                    '--skip-validation' => true,
                    '--no-telegram' => true,
                ]);
                if ($code !== 0) {
                    $this->error("Фаза 2.2 (импорт) завершилась с кодом $code.");
                    WorkerNotifier::notify("⛴ {$label}: фаза 2 — ошибка на импорте (код $code)");
                    return $code;
                }
            } else {
                $code = $this->call('worker:transfer', $transferArgs);
                if ($code !== 0) {
                    $this->error("Фаза 2 завершилась с кодом $code.");
                    WorkerNotifier::notify("⛴ {$label}: фаза 2 — ошибка (код $code)");
                    return $code;
                }
            }

            WorkerNotifier::notify("⛴ {$label}: фаза 2 — завершена");
        }

        $this->line('');
        $this->info('✅ Germes sync завершён');
        WorkerNotifier::notify("⛴ {$label} sync: готово");

        return 0;
    }

    protected function getOptions()
    {
        return [
            // phase switches
            ['parse-only', null, InputOption::VALUE_NONE, 'Выполнить только фазу 1 (API -> SQLite)'],
            ['transfer-only', null, InputOption::VALUE_NONE, 'Выполнить только фазу 2 (SQLite -> MySQL)'],
            ['import', null, InputOption::VALUE_NONE, 'Включить импорт на фазе 2 (по умолчанию только валидация)'],

            // phase 1 passthrough
            ['timeout', 't', InputOption::VALUE_OPTIONAL, 'Таймаут HTTP запросов (сек)', 30],
            ['clear', 'c', InputOption::VALUE_NONE, 'Очистить SQLite перед парсингом (фаза 1)'],
            ['clear_cache', null, InputOption::VALUE_NONE, 'Очистить кеш API Germes (фаза 1)'],
            ['limit', 'l', InputOption::VALUE_OPTIONAL, 'Лимит (для отладки/прогона) (фаза 1)', null],

            // phase 2 passthrough
            ['validate-only', null, InputOption::VALUE_NONE, 'Только валидация SQLite, без импорта (фаза 2)'],
            ['skip-validation', null, InputOption::VALUE_NONE, 'Пропустить валидацию (фаза 2)'],
        ];
    }
}

