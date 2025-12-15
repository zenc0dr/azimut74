<?php namespace Zen\Worker\Console\sync;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Zen\Worker\Console\transfer\TelegramNotifier;

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
        $startedAt = time();
        $tg = null;
        try {
            $tg = new TelegramNotifier();
            $tg->reset();
        } catch (\Throwable $e) {
            // Telegram не должен ломать выполнение команды
            $tg = null;
        }

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

        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('🚢 Waterway sync');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('Важно: кеш Waterway вечный (storage/parsers_cache/waterway) и не очищается без --clear_cache');

        $phase1Status = $transferOnly ? '⏭️' : '⏳';
        $phase2Status = $parseOnly ? '⏭️' : '⏳';

        if ($tg) {
            $tg->updateMessage($this->buildTelegramMessage($phase1Status, $phase2Status, 'Старт', $startedAt));
        }

        // Phase 1
        if (!$transferOnly) {
            $this->line('');
            $this->info('🟦 Фаза 1: API -> SQLite (worker:waterway-parse)');
            if ($tg) {
                $tg->updateMessage($this->buildTelegramMessage('🔄', $phase2Status, 'Фаза 1: парсинг (SQLite)', $startedAt));
            }
            $code = $this->call('worker:waterway-parse', $parseArgs);
            if ($code !== 0) {
                $this->error("Фаза 1 завершилась с кодом $code. Останавливаемся.");
                if ($tg) {
                    $tg->updateMessage($this->buildTelegramMessage('❌', $phase2Status, "Ошибка фазы 1 (код $code)", $startedAt));
                }
                return $code;
            }
            $phase1Status = '✅';
            if ($tg) {
                $tg->updateMessage($this->buildTelegramMessage($phase1Status, $phase2Status, 'Фаза 1 завершена', $startedAt));
            }
        }

        // Phase 2
        if (!$parseOnly) {
            $this->line('');
            $this->info('🟩 Фаза 2: SQLite -> MySQL (worker:transfer --source=waterway)');
            if ($tg) {
                $tg->updateMessage($this->buildTelegramMessage($phase1Status, '🔄', 'Фаза 2: перенос (валидация/импорт)', $startedAt));
            }

            // Отключаем Telegram внутри worker:transfer, чтобы не было второго сообщения.
            $transferArgs['--no-telegram'] = true;

            if ($doImport && !$skipValidation) {
                // 2-этапный режим: сначала валидация, затем импорт без повторной валидации.
                $this->info('🔍 Фаза 2.1: валидация SQLite (transfer --validate-only)');
                if ($tg) {
                    $tg->updateMessage($this->buildTelegramMessage($phase1Status, '🔄', 'Фаза 2.1: валидация', $startedAt));
                }

                $code = $this->call('worker:transfer', [
                    '--source' => 'waterway',
                    '--validate-only' => true,
                    '--no-telegram' => true,
                ]);
                if ($code !== 0) {
                    $this->error("Фаза 2.1 (валидация) завершилась с кодом $code.");
                    if ($tg) {
                        $tg->updateMessage($this->buildTelegramMessage($phase1Status, '❌', "Ошибка фазы 2.1 (код $code)", $startedAt));
                    }
                    return $code;
                }

                $this->info('✅ Фаза 2.1: валидация пройдена, начинаю импорт');
                if ($tg) {
                    $tg->updateMessage($this->buildTelegramMessage($phase1Status, '🔄', 'Фаза 2.1: валидация пройдена', $startedAt));
                }

                $this->info('📥 Фаза 2.2: импорт (transfer --skip-validation)');
                if ($tg) {
                    $tg->updateMessage($this->buildTelegramMessage($phase1Status, '🔄', 'Фаза 2.2: импорт', $startedAt));
                }

                $code = $this->call('worker:transfer', [
                    '--source' => 'waterway',
                    '--skip-validation' => true,
                    '--no-telegram' => true,
                ]);
                if ($code !== 0) {
                    $this->error("Фаза 2.2 (импорт) завершилась с кодом $code.");
                    if ($tg) {
                        $tg->updateMessage($this->buildTelegramMessage($phase1Status, '❌', "Ошибка фазы 2.2 (код $code)", $startedAt));
                    }
                    return $code;
                }
            } else {
                // Обычный режим: либо только валидация, либо импорт с/без валидации (как задано флагами)
                $code = $this->call('worker:transfer', $transferArgs);
                if ($code !== 0) {
                    $this->error("Фаза 2 завершилась с кодом $code.");
                    if ($tg) {
                        $tg->updateMessage($this->buildTelegramMessage($phase1Status, '❌', "Ошибка фазы 2 (код $code)", $startedAt));
                    }
                    return $code;
                }
            }
            $phase2Status = '✅';
            if ($tg) {
                $tg->updateMessage($this->buildTelegramMessage($phase1Status, $phase2Status, 'Фаза 2 завершена', $startedAt));
            }
        }

        $this->line('');
        $this->info('✅ Waterway sync завершён');
        if ($tg) {
            $tg->updateMessage($this->buildTelegramMessage($phase1Status, $phase2Status, 'Готово', $startedAt));
        }
        return 0;
    }

    private function buildTelegramMessage(string $phase1Icon, string $phase2Icon, string $stage, int $startedAt): string
    {
        $duration = time() - $startedAt;
        $mins = floor($duration / 60);
        $secs = $duration % 60;
        $durationStr = sprintf('%dm %02ds', $mins, $secs);

        $import = (bool)$this->option('import');
        $mode = $import ? 'импорт' : 'валидация';

        $lines = [];
        $lines[] = "🚢 <b>Waterway sync</b>";
        $lines[] = "🕒 <b>Стадия:</b> " . htmlspecialchars($stage);
        $lines[] = "⏱ <b>Длительность:</b> " . htmlspecialchars($durationStr);
        $lines[] = "";
        $lines[] = "{$phase1Icon} <b>Фаза 1</b>: API → SQLite";
        $lines[] = "{$phase2Icon} <b>Фаза 2</b>: SQLite → MySQL (" . htmlspecialchars($mode) . ")";
        $lines[] = "";
        $lines[] = "📁 Кеш: <code>storage/parsers_cache/waterway</code>";
        $lines[] = "💾 SQLite: <code>storage/parsers_db/waterway_data.sqlite</code>";

        return implode("\n", $lines);
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

            // phase 2 passthrough
            ['validate-only', null, InputOption::VALUE_NONE, 'Только валидация SQLite, без импорта (фаза 2)'],
            ['skip-validation', null, InputOption::VALUE_NONE, 'Пропустить валидацию (фаза 2)'],
        ];
    }
}

