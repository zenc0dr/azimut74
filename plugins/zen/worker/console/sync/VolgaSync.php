<?php namespace Zen\Worker\Console\sync;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Zen\Worker\Console\transfer\TelegramNotifier;

/**
 * VolgaSync
 *
 * Единая команда для Volga:
 * - Фаза 1: worker:volga-parse (XML -> SQLite)
 * - Фаза 2: worker:transfer --source=volga (SQLite -> MySQL)
 *
 * По умолчанию работает в validate-first:
 * - без --import выполняет только валидацию фазы 2
 * - с --import делает 2 шага: validate-only -> import(--skip-validation)
 */
class VolgaSync extends Command
{
    protected $name = 'worker:volga-sync';
    protected $description = 'Volga: parse (SQLite) + transfer (MySQL)';

    public function handle()
    {
        $startedAt = time();
        $tg = null;
        try {
            $tg = new TelegramNotifier();
            $tg->reset();
        } catch (\Throwable $e) {
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

        // --- Phase 1 args (volga-parse) ---
        $parseArgs = [
            '--timeout' => $this->option('timeout'),
            '--next-url' => $this->option('next-url'),
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
            '--source' => 'volga',
        ];

        if (!$doImport) {
            $transferArgs['--validate-only'] = true;
        } elseif ($skipValidation) {
            $transferArgs['--skip-validation'] = true;
        }

        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('🛶 Volga sync');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('Важно: XML кеш Volga вечный (storage/parsers_cache/volga) и не очищается без --clear_cache');

        $phase1Status = $transferOnly ? '⏭️' : '⏳';
        $phase2Status = $parseOnly ? '⏭️' : '⏳';

        if ($tg) {
            $tg->updateMessage($this->buildTelegramMessage($phase1Status, $phase2Status, 'Старт', $startedAt));
        }

        // Phase 1
        if (!$transferOnly) {
            $this->line('');
            $this->info('🟦 Фаза 1: XML -> SQLite (worker:volga-parse)');
            if ($tg) {
                $tg->updateMessage($this->buildTelegramMessage('🔄', $phase2Status, 'Фаза 1: парсинг (SQLite)', $startedAt));
            }

            $code = $this->call('worker:volga-parse', $parseArgs);
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
            $this->info('🟩 Фаза 2: SQLite -> MySQL (worker:transfer --source=volga)');
            if ($tg) {
                $tg->updateMessage($this->buildTelegramMessage($phase1Status, '🔄', 'Фаза 2: перенос (валидация/импорт)', $startedAt));
            }

            $transferArgs['--no-telegram'] = true;

            if ($doImport && !$skipValidation) {
                $this->info('🔍 Фаза 2.1: валидация SQLite (transfer --validate-only)');
                if ($tg) {
                    $tg->updateMessage($this->buildTelegramMessage($phase1Status, '🔄', 'Фаза 2.1: валидация', $startedAt));
                }

                $code = $this->call('worker:transfer', [
                    '--source' => 'volga',
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
                    '--source' => 'volga',
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
        $this->info('✅ Volga sync завершён');
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
        $lines[] = "🛶 <b>Volga sync</b>";
        $lines[] = "🕒 <b>Стадия:</b> " . htmlspecialchars($stage);
        $lines[] = "⏱ <b>Длительность:</b> " . htmlspecialchars($durationStr);
        $lines[] = "";
        $lines[] = "{$phase1Icon} <b>Фаза 1</b>: XML → SQLite";
        $lines[] = "{$phase2Icon} <b>Фаза 2</b>: SQLite → MySQL (" . htmlspecialchars($mode) . ")";
        $lines[] = "";
        $lines[] = "📁 Кеш: <code>storage/parsers_cache/volga</code>";
        $lines[] = "💾 SQLite: <code>storage/parsers_db/volga_data.sqlite</code>";

        return implode("\n", $lines);
    }

    protected function getOptions()
    {
        return [
            // phase switches
            ['parse-only', null, InputOption::VALUE_NONE, 'Выполнить только фазу 1 (XML -> SQLite)'],
            ['transfer-only', null, InputOption::VALUE_NONE, 'Выполнить только фазу 2 (SQLite -> MySQL)'],
            ['import', null, InputOption::VALUE_NONE, 'Включить импорт на фазе 2 (по умолчанию только валидация)'],

            // phase 1 passthrough
            ['timeout', 't', InputOption::VALUE_OPTIONAL, 'Таймаут HTTP запросов (сек)', 30],
            ['clear', 'c', InputOption::VALUE_NONE, 'Очистить SQLite перед парсингом (фаза 1)'],
            ['clear_cache', null, InputOption::VALUE_NONE, 'Очистить кеш XML Volga (фаза 1)'],
            ['limit', 'l', InputOption::VALUE_OPTIONAL, 'Лимит круизов (для отладки/прогона) (фаза 1)', null],
            ['next-url', 'u', InputOption::VALUE_OPTIONAL, 'URL источника XML данных (фаза 1)', 'http://test.volgawolga.ru/xml/daily2024.xml'],

            // phase 2 passthrough
            ['validate-only', null, InputOption::VALUE_NONE, 'Только валидация SQLite, без импорта (фаза 2)'],
            ['skip-validation', null, InputOption::VALUE_NONE, 'Пропустить валидацию (фаза 2)'],
        ];
    }
}

