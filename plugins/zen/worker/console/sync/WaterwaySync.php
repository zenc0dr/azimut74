<?php namespace Zen\Worker\Console\sync;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Zen\Worker\Classes\WorkerNotifier;

/**
 * WaterwaySync
 *
 * Единая команда для Waterway:
 * - Фаза 1: worker:waterway-parse (API -> SQLite, с вечным кешем storage/waterway_cache)
 * - Фаза 2: worker:transfer --source=waterway (SQLite -> MySQL)
 *
 * По умолчанию выполняет обе фазы последовательно, чтобы лог был линейным и понятным.
 */
class WaterwaySync extends Command
{
    protected $name = 'worker:waterway-sync';
    protected $description = 'Waterway: parse (SQLite) + transfer (MySQL)';

    public function handle()
    {
        $label = 'Waterway';

        $parseOnly = (bool)$this->option('parse-only');
        $transferOnly = (bool)$this->option('transfer-only');
        $doImport = (bool)$this->option('import');
        $validateOnly = (bool)$this->option('validate-only');
        $skipValidation = (bool)$this->option('skip-validation');
        $handleOnly = $this->option('handle_only');

        if ($parseOnly && $transferOnly) {
            $this->error('Нельзя одновременно указать --parse-only и --transfer-only');
            return 1;
        }

        if ($doImport && $validateOnly) {
            $this->error('Нельзя одновременно указать --import и --validate-only');
            return 1;
        }

        // --- Phase 1 args (waterway-parse) ---
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
        if ($this->option('limit_ships')) {
            $parseArgs['--limit_ships'] = $this->option('limit_ships');
        }
        if ($this->option('limit_cruises')) {
            $parseArgs['--limit_cruises'] = $this->option('limit_cruises');
        }
        if ($this->option('limit_cruises_per_ship')) {
            $parseArgs['--limit_cruises_per_ship'] = $this->option('limit_cruises_per_ship');
        }
        if ($this->option('progress_every')) {
            $parseArgs['--progress_every'] = $this->option('progress_every');
        }
        if ($handleOnly !== null && $handleOnly !== '') {
            $parseArgs['--handle_only'] = $handleOnly;
        }

        // --- Phase 2 args (transfer) ---
        $transferArgs = [
            '--source' => 'waterway',
        ];

        // validate-first по умолчанию: импорт включается только явным --import
        if (!$doImport) {
            $transferArgs['--validate-only'] = true;
        } elseif ($skipValidation) {
            // Если пользователь явно просит пропустить валидацию, перенесём это в импортную фазу
            $transferArgs['--skip-validation'] = true;
        }

        if ($handleOnly !== null && $handleOnly !== '') {
            $transferArgs['--handle_only'] = $handleOnly;
        }

        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('🚢 Waterway sync');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('Важно: кеш Waterway вечный (storage/parsers_cache/waterway) и не очищается без --clear_cache');

        WorkerNotifier::notify("🚢 {$label} sync: старт");

        // Phase 1
        if (!$transferOnly) {
            $this->line('');
            $this->info('🟦 Фаза 1: API -> SQLite (worker:waterway-parse)');
            WorkerNotifier::notify("🚢 {$label}: фаза 1 (парсинг в SQLite) — начало");
            $code = $this->call('worker:waterway-parse', $parseArgs);
            if ($code !== 0) {
                $this->error("Фаза 1 завершилась с кодом $code. Останавливаемся.");
                WorkerNotifier::notify("🚢 {$label}: фаза 1 — ошибка (код выхода $code)");
                return $code;
            }
            WorkerNotifier::notify("🚢 {$label}: фаза 1 — завершена");
        }

        // Phase 2
        if (!$parseOnly) {
            $this->line('');
            $this->info('🟩 Фаза 2: SQLite -> MySQL (worker:transfer --source=waterway)');
            WorkerNotifier::notify("🚢 {$label}: фаза 2 (перенос в MySQL) — начало");

            // Без уведомлений внутри worker:transfer — их даёт эта sync-команда.
            $transferArgs['--no-telegram'] = true;

            if ($doImport && !$skipValidation) {
                // 2-этапный режим: сначала валидация, затем импорт без повторной валидации.
                $this->info('🔍 Фаза 2.1: валидация SQLite (transfer --validate-only)');

                $phase21Args = [
                    '--source' => 'waterway',
                    '--validate-only' => true,
                    '--no-telegram' => true,
                ];
                if ($handleOnly !== null && $handleOnly !== '') {
                    $phase21Args['--handle_only'] = $handleOnly;
                }
                $code = $this->call('worker:transfer', $phase21Args);
                if ($code !== 0) {
                    $this->error("Фаза 2.1 (валидация) завершилась с кодом $code.");
                    WorkerNotifier::notify("🚢 {$label}: фаза 2 — ошибка на валидации (код $code)");
                    return $code;
                }

                $this->info('✅ Фаза 2.1: валидация пройдена, начинаю импорт');

                $this->info('📥 Фаза 2.2: импорт (transfer --skip-validation)');

                $phase22Args = [
                    '--source' => 'waterway',
                    '--skip-validation' => true,
                    '--no-telegram' => true,
                ];
                if ($handleOnly !== null && $handleOnly !== '') {
                    $phase22Args['--handle_only'] = $handleOnly;
                }
                $code = $this->call('worker:transfer', $phase22Args);
                if ($code !== 0) {
                    $this->error("Фаза 2.2 (импорт) завершилась с кодом $code.");
                    WorkerNotifier::notify("🚢 {$label}: фаза 2 — ошибка на импорте (код $code)");
                    return $code;
                }
            } else {
                // Обычный режим: либо только валидация, либо импорт с/без валидации (как задано флагами)
                $code = $this->call('worker:transfer', $transferArgs);
                if ($code !== 0) {
                    $this->error("Фаза 2 завершилась с кодом $code.");
                    WorkerNotifier::notify("🚢 {$label}: фаза 2 — ошибка (код $code)");
                    return $code;
                }
            }
            WorkerNotifier::notify("🚢 {$label}: фаза 2 — завершена");
        }

        $this->line('');
        $this->info('✅ Waterway sync завершён');
        WorkerNotifier::notify("🚢 {$label} sync: готово");
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
            ['clear_cache', null, InputOption::VALUE_NONE, 'Очистить кеш API Waterway (фаза 1)'],
            ['limit', 'l', InputOption::VALUE_OPTIONAL, 'Legacy лимит круизов (фаза 1)', null],
            ['limit_ships', null, InputOption::VALUE_OPTIONAL, 'Лимит теплоходов (фаза 1)', null],
            ['limit_cruises', null, InputOption::VALUE_OPTIONAL, 'Лимит круизов (фаза 1)', null],
            ['limit_cruises_per_ship', null, InputOption::VALUE_OPTIONAL, 'Лимит круизов на теплоход (фаза 1)', null],
            ['progress_every', null, InputOption::VALUE_OPTIONAL, 'Прогресс в консоль каждые N круизов (фаза 1)', 1],
            ['handle_only', null, InputOption::VALUE_OPTIONAL, 'Только круиз по eds_id Waterway (обе фазы)', null],

            // phase 2 passthrough
            ['validate-only', null, InputOption::VALUE_NONE, 'Только валидация SQLite, без импорта (фаза 2)'],
            ['skip-validation', null, InputOption::VALUE_NONE, 'Пропустить валидацию (фаза 2)'],
        ];
    }
}

