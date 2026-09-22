<?php namespace Zen\Worker\Classes;

use DB;
use Exception;
use Illuminate\Console\Command;
use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use Zen\Cabox\Classes\Cabox;
use Zen\Cabox\Models\Storage as CaboxStorage;
use Zen\Worker\Classes\SearchCacheVersion;
use Zen\Worker\Console\gama\GamaCache;
use Zen\Worker\Console\germes\GermesCache;
use Zen\Worker\Console\infoflot\InfoflotCache;
use Zen\Worker\Console\waterway\WaterwayCache;
use Zen\Worker\Console\volga\VolgaApiClient;

class CheckinActualizer
{
    const SOURCES = ['waterway', 'infoflot', 'volga', 'germes', 'gama'];
    const MAX_ATTEMPTS = 3;
    const MIN_INTERVAL_SECONDS = 3600;
    const RUN_STAMP_FILE = 'parsers_run_started_at';

    /** @var Command */
    protected $command;

    /** @var bool */
    protected $dryRun;

    public function __construct(Command $command, bool $dryRun = false)
    {
        $this->command = $command;
        $this->dryRun = $dryRun;
    }

    public static function runStampPath(): string
    {
        return storage_path(self::RUN_STAMP_FILE);
    }

    public function resolveRunStartedAt(?string $optionValue = null): string
    {
        if ($optionValue) {
            return date('Y-m-d H:i:s', strtotime($optionValue));
        }
        $path = self::runStampPath();
        if (is_file($path)) {
            $raw = trim((string) file_get_contents($path));
            if ($raw !== '') {
                $ts = strtotime($raw);
                if ($ts) {
                    return date('Y-m-d H:i:s', $ts);
                }
            }
        }
        return date('Y-m-d 00:00:00');
    }

    public function expire(?int $onlyCheckinId = null): array
    {
        $query = DB::table('mcmraak_rivercrs_checkins')
            ->whereRaw('DATE(`date`) < CURDATE()');
        if ($onlyCheckinId) {
            $query->where('id', $onlyCheckinId);
        }
        $rows = $query->select('id', 'eds_code', 'eds_id', 'motorship_id', 'date')->get();
        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int) $row->id;
            $this->command->line(sprintf(
                '  expire checkin_id=%d eds=%s:%s date=%s',
                $row->id,
                $row->eds_code,
                $row->eds_id,
                $row->date
            ));
        }
        if (!$this->dryRun) {
            foreach ($ids as $id) {
                $this->deleteCheckin($id);
                DB::table('mcmraak_rivercrs_checkin_probes')->where('checkin_id', $id)->delete();
            }
        }
        return ['count' => count($ids), 'ids' => $ids];
    }

    public function seedQueue(string $runStartedAt, ?int $onlyCheckinId = null): int
    {
        $query = DB::table('mcmraak_rivercrs_checkins as c')
            ->leftJoin('mcmraak_rivercrs_checkins_memory as m', 'm.checkin_id', '=', 'c.id')
            ->whereIn('c.eds_code', self::SOURCES)
            ->whereRaw('DATE(c.`date`) >= CURDATE()')
            ->where(function ($q) use ($runStartedAt) {
                $q->whereNull('m.updated_at')
                    ->orWhere('m.updated_at', '<', $runStartedAt);
            });
        if ($onlyCheckinId) {
            $query->where('c.id', $onlyCheckinId);
        }
        $rows = $query->select(
            'c.id',
            'c.eds_code',
            'c.eds_id',
            'c.motorship_id',
            'c.date'
        )->get();

        $inserted = 0;
        foreach ($rows as $row) {
            $this->command->line(sprintf(
                '  candidate checkin_id=%d eds=%s:%s ship=%s date=%s',
                $row->id,
                $row->eds_code,
                $row->eds_id,
                $row->motorship_id,
                $row->date
            ));
            if ($this->dryRun) {
                $inserted++;
                continue;
            }
            $existing = DB::table('mcmraak_rivercrs_checkin_probes')->where('checkin_id', $row->id)->first();
            if ($existing) {
                if ($existing->status === 'ok' || $existing->status === 'withdrawn') {
                    if ($existing->run_started_at !== $runStartedAt) {
                        DB::table('mcmraak_rivercrs_checkin_probes')->where('id', $existing->id)->update([
                            'eds_code' => $row->eds_code,
                            'eds_id' => (int) $row->eds_id,
                            'motorship_id' => $row->motorship_id,
                            'run_started_at' => $runStartedAt,
                            'attempts' => 0,
                            'last_attempt_at' => null,
                            'last_error' => null,
                            'status' => 'pending',
                        ]);
                        $inserted++;
                    }
                } elseif ($existing->run_started_at !== $runStartedAt) {
                    DB::table('mcmraak_rivercrs_checkin_probes')->where('id', $existing->id)->update([
                        'eds_code' => $row->eds_code,
                        'eds_id' => (int) $row->eds_id,
                        'motorship_id' => $row->motorship_id,
                        'run_started_at' => $runStartedAt,
                        'attempts' => 0,
                        'last_attempt_at' => null,
                        'last_error' => null,
                        'status' => 'pending',
                    ]);
                    $inserted++;
                }
                continue;
            }
            DB::table('mcmraak_rivercrs_checkin_probes')->insert([
                'checkin_id' => (int) $row->id,
                'eds_code' => $row->eds_code,
                'eds_id' => (int) $row->eds_id,
                'motorship_id' => $row->motorship_id,
                'run_started_at' => $runStartedAt,
                'attempts' => 0,
                'status' => 'pending',
            ]);
            $inserted++;
        }
        return $inserted;
    }

    public function probe(string $runStartedAt, ?int $onlyCheckinId = null): array
    {
        $stats = ['ok' => 0, 'fail' => 0, 'skipped' => 0, 'sources' => []];
        if (!$this->dryRun) {
            $this->seedQueue($runStartedAt, $onlyCheckinId);
        }

        $query = DB::table('mcmraak_rivercrs_checkin_probes')->where('status', 'pending');
        if ($onlyCheckinId) {
            $query->where('checkin_id', $onlyCheckinId);
        }
        $pending = $query->get();
        if ($this->dryRun && $pending->isEmpty()) {
            $this->command->info('dry-run: очередь пуста, показываю кандидатов без записи в probes');
            $this->seedQueue($runStartedAt, $onlyCheckinId);
            return $stats;
        }

        $due = [];
        $now = time();
        foreach ($pending as $row) {
            if ((int) $row->attempts > 0 && $row->last_attempt_at) {
                $elapsed = $now - strtotime($row->last_attempt_at);
                if ($elapsed < self::MIN_INTERVAL_SECONDS) {
                    $stats['skipped']++;
                    $this->command->line(sprintf(
                        '  skip checkin_id=%d: интервал %d сек < 1ч',
                        $row->checkin_id,
                        $elapsed
                    ));
                    continue;
                }
            }
            $due[] = $row;
        }

        $grouped = [];
        foreach ($due as $row) {
            $grouped[$row->eds_code][] = $row;
        }

        foreach ($grouped as $edsCode => $rows) {
            $cruiseIds = [];
            $shipIds = [];
            foreach ($rows as $row) {
                if ((int) $row->eds_id > 0) {
                    $cruiseIds[] = (int) $row->eds_id;
                }
                $sourceShipId = $this->sourceShipId((int) $row->motorship_id, $edsCode);
                if ($sourceShipId) {
                    $shipIds[] = $sourceShipId;
                }
            }
            $cruiseIds = array_values(array_unique($cruiseIds));
            $shipIds = array_values(array_unique($shipIds));
            $stats['sources'][$edsCode] = count($cruiseIds);

            $this->command->info(sprintf(
                'probe %s: круизов=%d теплоходов=%d',
                $edsCode,
                count($cruiseIds),
                count($shipIds)
            ));

            if ($this->dryRun) {
                continue;
            }

            $this->bustSourceCache($edsCode, $cruiseIds, $shipIds);
            $attemptAt = date('Y-m-d H:i:s');
            foreach ($rows as $row) {
                DB::table('mcmraak_rivercrs_checkin_probes')->where('id', $row->id)->update([
                    'last_attempt_at' => $attemptAt,
                    'attempts' => (int) $row->attempts + 1,
                ]);
            }

            try {
                $this->runTargetedSync($edsCode, $cruiseIds, $shipIds);
            } catch (Exception $e) {
                $this->command->error($e->getMessage());
                foreach ($rows as $row) {
                    DB::table('mcmraak_rivercrs_checkin_probes')->where('id', $row->id)->update([
                        'last_error' => $e->getMessage(),
                    ]);
                    $stats['fail']++;
                }
                continue;
            }

            foreach ($rows as $row) {
                $memory = DB::table('mcmraak_rivercrs_checkins_memory')
                    ->where('checkin_id', $row->checkin_id)
                    ->first();
                $fresh = $memory && $memory->updated_at && strtotime($memory->updated_at) >= strtotime($attemptAt);
                if ($fresh) {
                    DB::table('mcmraak_rivercrs_checkin_probes')->where('id', $row->id)->update([
                        'status' => 'ok',
                        'last_error' => null,
                    ]);
                    $stats['ok']++;
                    $this->command->line("  ok checkin_id={$row->checkin_id}");
                } else {
                    DB::table('mcmraak_rivercrs_checkin_probes')->where('id', $row->id)->update([
                        'last_error' => 'круиз не подтверждён источником',
                    ]);
                    $stats['fail']++;
                    $this->command->line("  fail checkin_id={$row->checkin_id} eds={$row->eds_code}:{$row->eds_id}");
                }
            }
        }

        return $stats;
    }

    public function withdraw(?int $onlyCheckinId = null): array
    {
        $query = DB::table('mcmraak_rivercrs_checkin_probes')
            ->where('status', 'pending')
            ->where('attempts', '>=', self::MAX_ATTEMPTS);
        if ($onlyCheckinId) {
            $query->where('checkin_id', $onlyCheckinId);
        }
        $rows = $query->get();
        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int) $row->checkin_id;
            $this->command->line(sprintf(
                '  withdraw checkin_id=%d eds=%s:%s attempts=%d',
                $row->checkin_id,
                $row->eds_code,
                $row->eds_id,
                $row->attempts
            ));
            if ($this->dryRun) {
                continue;
            }
            $this->deleteCheckin((int) $row->checkin_id);
            DB::table('mcmraak_rivercrs_checkin_probes')->where('id', $row->id)->update([
                'status' => 'withdrawn',
            ]);
        }
        return ['count' => count($ids), 'ids' => $ids];
    }

    public function deleteCheckin(int $id): void
    {
        DB::table('mcmraak_rivercrs_checkins_memory')->where('checkin_id', $id)->delete();
        $checkin = Checkin::find($id);
        if ($checkin) {
            $checkin->delete();
        }
        $cabox = new Cabox('rivercrs');
        $cabox->del('rcrs:' . $id);
        $cabox->del('exist_array:' . $id);
        $cabox->del('exist:' . $id);
    }

    /**
     * Сброс того, что видит фронт: Cabox rivercrs (storage id 9), CMS-кеш, версия поиска.
     *
     * @return array{cabox:bool,cms:bool,search_version:int|null}
     */
    public function refreshFrontendCaches(): array
    {
        $result = [
            'cabox' => false,
            'cms' => false,
            'search_version' => null,
        ];

        if ($this->dryRun) {
            $this->command->info('dry-run: пропуск сброса Cabox rivercrs, CMS-кеша и версии поиска');
            return $result;
        }

        $storage = CaboxStorage::find(9);
        if (!$storage) {
            $storage = CaboxStorage::where('code', 'rivercrs')->first();
        }
        if ($storage) {
            $purge = $storage->purge();
            if ($purge instanceof Exception) {
                $this->command->error('Cabox purge: ' . $purge->getMessage());
            } else {
                $result['cabox'] = true;
                $this->command->info('Cabox: очищено хранилище id=' . $storage->id . ' code=' . $storage->code);
            }
        } else {
            $this->command->error('Cabox: хранилище id=9 / code=rivercrs не найдено');
        }

        try {
            \System\Helpers\Cache::clear();
            $result['cms'] = true;
            $this->command->info('CMS: сброшен cache + cms/cache, twig, combiner');
        } catch (Exception $e) {
            $this->command->error('CMS cache clear: ' . $e->getMessage());
        }

        $newVersion = SearchCacheVersion::increment();
        $result['search_version'] = $newVersion;
        $this->command->info('Версия поискового кеша: ' . $newVersion);

        return $result;
    }

    protected function sourceShipId(int $motorshipId, string $edsCode): ?int
    {
        if ($motorshipId < 1) {
            return null;
        }
        $field = $edsCode . '_id';
        $ship = DB::table('mcmraak_rivercrs_motorships')->where('id', $motorshipId)->first();
        if (!$ship || !isset($ship->{$field})) {
            return null;
        }
        $id = (int) $ship->{$field};
        return $id > 0 ? $id : null;
    }

    protected function bustSourceCache(string $edsCode, array $cruiseIds, array $shipIds): void
    {
        if ($edsCode === 'waterway') {
            $cache = new WaterwayCache();
            foreach ($cruiseIds as $id) {
                $cache->forget("waterway_cruise_detail_{$id}");
                $cache->forget("waterway_cruise_{$id}");
                $cache->forget("waterway_prices_{$id}");
                $cache->forget("waterway_route_v2_{$id}");
            }
            return;
        }
        if ($edsCode === 'infoflot') {
            $cache = new InfoflotCache();
            $date = date('Y-m-d');
            foreach ($cruiseIds as $id) {
                $cache->forget("infoflot_cruise_{$id}_cabins");
            }
            foreach ($shipIds as $shipId) {
                for ($page = 1; $page <= 30; $page++) {
                    $cache->forget("infoflot_cruises_ship_{$shipId}_page_{$page}_date_{$date}");
                }
            }
            return;
        }
        if ($edsCode === 'gama') {
            $cache = new GamaCache();
            foreach ($cruiseIds as $id) {
                $cache->forget("gama_route_{$id}");
            }
            return;
        }
        if ($edsCode === 'germes') {
            $cache = new GermesCache();
            $cache->forget('germes_cruises');
            foreach ($cruiseIds as $id) {
                $cache->forget("germes_trace_{$id}");
                $cache->forget("germes_prices_{$id}");
            }
            return;
        }
        if ($edsCode === 'volga') {
            $url = 'https://test.volgawolga.ru/xml/daily2026.xml';
            (new VolgaApiClient($url))->clearCache();
        }
    }

    protected function runTargetedSync(string $edsCode, array $cruiseIds, array $shipIds): void
    {
        $command = 'worker:' . $edsCode . '-sync';
        $args = [
            '--import' => true,
            '--skip-validation' => true,
        ];
        if ($cruiseIds) {
            $args['--cruise_ids'] = IdList::join($cruiseIds);
        }
        if ($shipIds) {
            $args['--ship_ids'] = IdList::join($shipIds);
        }
        if ($edsCode === 'volga') {
            $args['--clear_cache'] = true;
        }
        $code = $this->command->call($command, $args);
        if ($code !== 0) {
            throw new Exception("{$command} завершился с кодом {$code}");
        }
    }
}
