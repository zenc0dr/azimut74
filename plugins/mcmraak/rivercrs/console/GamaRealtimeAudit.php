<?php namespace Mcmraak\Rivercrs\Console;

use Illuminate\Console\Command;
use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use Symfony\Component\Console\Input\InputOption;

class GamaRealtimeAudit extends Command
{
    protected $name = 'rivercrs:gama-realtime-audit';

    protected $description = 'Проверка realtime-ответов Гама по всем активным заездам';

    protected function getOptions()
    {
        return [
            [
                'limit',
                null,
                InputOption::VALUE_OPTIONAL,
                'Ограничить количество проверяемых заездов (для отладки).',
                null,
            ],
            [
                'checkin-id',
                null,
                InputOption::VALUE_OPTIONAL,
                'Проверить только один checkin_id.',
                null,
            ],
        ];
    }

    public function handle()
    {
        $startedAt = date('Y-m-d H:i:s');
        $logDir = storage_path('logs/gama-realtime-audit');
        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        $logFile = $logDir . '/gama-realtime-audit-' . date('Ymd-His') . '.json';

        $query = Checkin::query()
            ->where('eds_code', 'gama')
            ->where('active', 1)
            ->orderBy('date');

        $singleCheckinId = intval($this->option('checkin-id'));
        if ($singleCheckinId > 0) {
            $query->where('id', $singleCheckinId);
        }

        $limit = intval($this->option('limit'));
        if ($limit > 0) {
            $query->limit($limit);
        }

        $checkins = $query->get();
        $total = $checkins->count();

        $this->info("Гама realtime аудит: найдено заездов = {$total}");
        $this->line("Лог запуска: {$logFile}");

        $stats = [
            'ok' => 0,
            'fail' => 0,
            'errors' => 0,
            'deactivated' => 0,
        ];
        $items = [];

        $index = 0;
        foreach ($checkins as $checkin) {
            $index++;
            $prefix = "[{$index}/{$total}] checkin_id={$checkin->id}, eds_id={$checkin->eds_id}";
            $this->line("{$prefix} -> проверка...");

            $itemStartedAt = microtime(true);
            $item = [
                'checkin_id' => intval($checkin->id),
                'eds_id' => intval($checkin->eds_id),
                'date' => (string) $checkin->date,
                'status' => 'fail',
                'reason' => null,
                'decks_count' => 0,
                'prices_count' => 0,
                'min_price' => null,
                'elapsed_ms' => null,
                'deactivated' => false,
            ];

            try {
                $existData = app('Mcmraak\Rivercrs\Classes\Exist')
                    ->get($checkin, 'array', true, true);

                if (!is_array($existData)) {
                    $item['reason'] = 'invalid_response_type';
                    $stats['errors']++;
                } else {
                    $item['decks_count'] = isset($existData['decks']) && is_array($existData['decks'])
                        ? count($existData['decks'])
                        : 0;

                    $minPrice = null;
                    $pricesCount = 0;

                    foreach (($existData['decks'] ?? []) as $deck) {
                        foreach (($deck['cabins'] ?? []) as $cabin) {
                            foreach (($cabin['prices'] ?? []) as $price) {
                                $priceValue = intval($price['price_value'] ?? 0);
                                if ($priceValue <= 0) {
                                    continue;
                                }

                                $pricesCount++;
                                if ($minPrice === null || $priceValue < $minPrice) {
                                    $minPrice = $priceValue;
                                }
                            }
                        }
                    }

                    $item['prices_count'] = $pricesCount;
                    $item['min_price'] = $minPrice;

                    if ($pricesCount > 0) {
                        $item['status'] = 'ok';
                        $item['reason'] = null;
                        $stats['ok']++;
                    } else {
                        $item['status'] = 'fail';
                        $item['reason'] = 'no_realtime_prices';
                        $stats['fail']++;
                    }
                }
            } catch (\Throwable $exception) {
                $item['status'] = 'fail';
                $item['reason'] = 'exception: ' . $exception->getMessage();
                $stats['errors']++;
            }

            $item['elapsed_ms'] = intval((microtime(true) - $itemStartedAt) * 1000);

            // По требованиям задачи: при FAIL заезд деактивируется.
            if ($item['status'] === 'fail' && intval($checkin->active) === 1) {
                $checkin->active = 0;
                $checkin->save();
                $item['deactivated'] = true;
                $stats['deactivated']++;
            }

            $items[] = $item;

            if ($item['status'] === 'ok') {
                $this->info("{$prefix} -> OK, min={$item['min_price']}, prices={$item['prices_count']}, decks={$item['decks_count']}");
            } else {
                $deactivatedText = $item['deactivated'] ? ', deactivated=1' : '';
                $this->warn("{$prefix} -> FAIL, reason={$item['reason']}, prices={$item['prices_count']}, decks={$item['decks_count']}{$deactivatedText}");
            }
        }

        $report = [
            'meta' => [
                'command' => $this->name,
                'started_at' => $startedAt,
                'finished_at' => date('Y-m-d H:i:s'),
                'total' => $total,
                'ok' => $stats['ok'],
                'fail' => $stats['fail'],
                'errors' => $stats['errors'],
                'deactivated' => $stats['deactivated'],
                'options' => [
                    'limit' => $limit > 0 ? $limit : null,
                    'checkin_id' => $singleCheckinId > 0 ? $singleCheckinId : null,
                ],
            ],
            'items' => $items,
        ];

        file_put_contents(
            $logFile,
            json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );

        $this->line('---');
        $this->info("Итог: OK={$stats['ok']}, FAIL={$stats['fail']}, ERRORS={$stats['errors']}, DEACTIVATED={$stats['deactivated']}");
        $this->line("Отчёт: {$logFile}");

        return 0;
    }
}
