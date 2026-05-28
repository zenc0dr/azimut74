<?php namespace Mcmraak\Rivercrs\Console;

use Illuminate\Console\Command;
use DB;
use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use Symfony\Component\Console\Input\InputOption;

class WaterwayRealtimeAudit extends Command
{
    protected $name = 'rivercrs:waterway-realtime-audit';

    protected $description = 'Проверка realtime-ответов Водохода по активным заездам';

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
        $logDir = storage_path('logs/waterway-realtime-audit');
        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        $logFile = $logDir . '/waterway-realtime-audit-' . date('Ymd-His') . '.json';

        $query = Checkin::query()
            ->where('eds_code', 'waterway')
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

        $this->info("Водоход realtime аудит: найдено заездов = {$total}");
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

                    if (!empty($existData['realtime_unavailable'])) {
                        $item['reason'] = $existData['reason'] ?? 'realtime_unavailable';
                        $stats['fail']++;
                    } else {
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
                            $stats['ok']++;
                        } else {
                            $item['reason'] = 'no_realtime_prices';
                            $stats['fail']++;
                        }
                    }
                }
            } catch (\Throwable $exception) {
                $item['status'] = 'fail';
                $item['reason'] = 'exception: ' . $exception->getMessage();
                $stats['errors']++;
            }

            $item['elapsed_ms'] = intval((microtime(true) - $itemStartedAt) * 1000);

            if ($item['status'] === 'fail' && intval($checkin->active) === 1) {
                DB::table('mcmraak_rivercrs_checkins')
                    ->where('id', $checkin->id)
                    ->update(['active' => 0]);
                $item['deactivated'] = true;
                $stats['deactivated']++;
            }

            $items[] = $item;

            if ($item['status'] === 'ok') {
                $this->info("{$prefix} -> OK, min={$item['min_price']}, prices={$item['prices_count']}");
            } else {
                $deactivatedText = $item['deactivated'] ? ', deactivated=1' : '';
                $this->warn("{$prefix} -> FAIL, reason={$item['reason']}{$deactivatedText}");
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
            ],
            'items' => $items,
        ];

        file_put_contents($logFile, json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $this->info("Итог: OK={$stats['ok']}, FAIL={$stats['fail']}, ERRORS={$stats['errors']}, DEACTIVATED={$stats['deactivated']}");
    }
}
