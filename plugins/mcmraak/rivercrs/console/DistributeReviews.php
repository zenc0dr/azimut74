<?php namespace Mcmraak\Rivercrs\Console;

use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;
use Mcmraak\Rivercrs\Classes\CruiseReviewAssignments;
use Mcmraak\Rivercrs\Classes\ReviewsDistributionCsv;
use Mcmraak\Rivercrs\Classes\ReviewsWidget;
use Mcmraak\Rivercrs\Models\Checkins;
use Mcmraak\Rivercrs\Models\Motorships;
use Symfony\Component\Console\Input\InputOption;
use Zen\Reviews\Models\Review as ZenReview;

class DistributeReviews extends Command
{
    protected $name = 'rivercrs:distribute-reviews';

    protected $description = 'Полное распределение отзывов: круизы (файл) → теплоходы → посадочные по CSV';

    public const MOTORSHIP_BINDINGS_LIMIT = 4;

    /** @var ReviewsDistributionCsv */
    private $distributionCsv;

    protected function getOptions()
    {
        return [
            [
                'csv-url',
                null,
                InputOption::VALUE_OPTIONAL,
                'URL публичного CSV (Google Sheets).',
                ReviewsDistributionCsv::DEFAULT_PUBLISH_URL,
            ],
            [
                'csv',
                null,
                InputOption::VALUE_OPTIONAL,
                'Локальный CSV вместо скачивания (относительно base_path).',
                null,
            ],
            [
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Проверка без изменений в БД и без записи файла круизов.',
                null,
            ],
        ];
    }

    public function handle()
    {
        $this->distributionCsv = new ReviewsDistributionCsv();
        $dryRun = (bool) $this->option('dry-run');

        $csvPath = $this->resolveDistributionCsvPath();
        if (!$csvPath) {
            $this->error('Не удалось получить CSV распределения (скачивание или --csv).');
            return 1;
        }

        $rows = $this->distributionCsv->parseCsv($csvPath);
        if ($rows === []) {
            $this->error('В CSV нет валидных строк.');
            return 1;
        }

        [$landingRows, $resolveErrors] = $this->resolveLandingRows($rows);
        [$landingRows, $mergeWarnings] = $this->distributionCsv->mergeResolvedRowsByTarget($landingRows);

        $this->info('CSV: ' . $csvPath);
        $this->info('Строк посадочных (cruise_menu/transit/index): ' . count($landingRows));

        if (!$dryRun) {
            $this->info('Очищаю все zen_reviews_bindings...');
            DB::table('zen_reviews_bindings')->truncate();
        }

        $shipPools = $this->buildReviewIdsByShipId();

        // Фаза 1: круизы → файл
        [$cruiseAssignments, $cruiseStats] = $this->runPhaseCruiseAssignments($dryRun, $shipPools);
        $excludedFromCruiseFile = CruiseReviewAssignments::excludedReviewIdMap($cruiseAssignments);

        $this->info(sprintf(
            'Фаза 1 (круизы → файл): заездов=%d, слотов=%d, уникальных review_id=%d',
            $cruiseStats['checkins'],
            $cruiseStats['slots'],
            count($excludedFromCruiseFile)
        ));

        // Фаза 2: теплоходы → bindings
        [$motorshipBindings, $motorshipStats] = $this->runPhaseMotorship(
            $excludedFromCruiseFile,
            $dryRun,
            $shipPools
        );
        $usedReviewIds = $motorshipBindings;

        $this->info(sprintf(
            'Фаза 2 (теплоходы): судов=%d, привязок=%d',
            $motorshipStats['ships'],
            $motorshipStats['assigned']
        ));

        // Фаза 3: посадочные → bindings по CSV
        [$landingStats, $shortages] = $this->runPhaseLanding(
            $landingRows,
            $excludedFromCruiseFile,
            $usedReviewIds,
            $dryRun
        );

        $this->info(sprintf(
            'Фаза 3 (посадочные CSV): запрошено=%d, назначено=%d, недобор страниц=%d',
            $landingStats['requested'],
            $landingStats['assigned'],
            count($shortages)
        ));

        if (!$dryRun) {
            $this->info('Всего привязок в БД: ' . (int) DB::table('zen_reviews_bindings')->count());
            $this->info('Файл круизов: ' . CruiseReviewAssignments::storagePath());
        } else {
            $this->info('DRY-RUN: БД и файл круизов не изменялись.');
        }

        $this->printReport($resolveErrors, $mergeWarnings, $shortages, $cruiseStats, $motorshipStats, $dryRun);

        return 0;
    }

    private function resolveDistributionCsvPath(): ?string
    {
        $local = $this->distributionCsv->resolveCsvPath((string) $this->option('csv'));
        if ($local) {
            $this->info('CSV (локальный): ' . $local);
            return $local;
        }

        $path = $this->distributionCsv->downloadToStorage((string) $this->option('csv-url'));
        if ($path) {
            $this->info('CSV скачан: ' . $path);
            return $path;
        }

        return $this->distributionCsv->resolveCsvPath(ReviewsDistributionCsv::DEFAULT_STORAGE_RELATIVE);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array{0: array, 1: array}
     */
    private function resolveLandingRows(array $rows): array
    {
        $landing = [];
        $errors = [];
        [$resolved, $resolveErrors] = $this->distributionCsv->resolveTargets($rows);

        foreach ($resolved as $row) {
            $type = $row['target']['entity_type'] ?? '';
            if ($type === ReviewsWidget::ENTITY_MOTORSHIP) {
                continue;
            }
            $landing[] = $row;
        }

        foreach ($resolveErrors as $error) {
            if (($error['page_type'] ?? '') === 'motorship') {
                continue;
            }
            $errors[] = $error;
        }

        return [$landing, $errors];
    }

    /**
     * @return array{0: array<string, array<int, int>>, 1: array<string, int>}
     */
    /**
     * @param array<int, array<int, int>> $shipPools
     * @return array{0: array<string, array<int, int>>, 1: array<string, int>}
     */
    private function runPhaseCruiseAssignments(bool $dryRun, array $shipPools): array
    {
        $assignments = [];
        $stats = ['checkins' => 0, 'slots' => 0, 'short_checkins' => 0];
        $limit = CruiseReviewAssignments::REVIEWS_PER_CHECKIN;

        Checkins::query()
            ->where('active', 1)
            ->where('motorship_id', '>', 0)
            ->orderBy('id')
            ->select(['id', 'motorship_id'])
            ->chunk(500, function ($checkins) use (&$assignments, &$stats, $shipPools, $limit) {
                foreach ($checkins as $checkin) {
                    $checkinId = (int) $checkin->id;
                    $shipId = (int) $checkin->motorship_id;
                    if ($checkinId < 1 || $shipId < 1) {
                        continue;
                    }

                    $pool = $shipPools[$shipId] ?? [];
                    if ($pool === []) {
                        $assignments[(string) $checkinId] = [];
                        $stats['checkins']++;
                        $stats['short_checkins']++;
                        continue;
                    }

                    $picked = $pool;
                    shuffle($picked);
                    $picked = array_slice($picked, 0, min($limit, count($picked)));

                    $assignments[(string) $checkinId] = $picked;
                    $stats['checkins']++;
                    $stats['slots'] += count($picked);
                    if (count($picked) < $limit) {
                        $stats['short_checkins']++;
                    }
                }
            });

        if (!$dryRun) {
            CruiseReviewAssignments::save($assignments);
        }

        return [$assignments, $stats];
    }

    /**
     * @return array<int, array<int, int>> ship_id => [review_id, ...]
     */
    private function buildReviewIdsByShipId(): array
    {
        $pools = [];
        foreach (ZenReview::query()->select(['id', 'data'])->cursor() as $review) {
            $form = ReviewsWidget::extractForm($review);
            $shipId = ReviewsWidget::normalizeShipId($form['ship_id'] ?? null);
            if ($shipId < 1) {
                continue;
            }
            $pools[$shipId][] = (int) $review->id;
        }

        return $pools;
    }

    /**
     * @param array<int, bool> $excludedFromCruiseFile
     * @return array{0: array<int, bool>, 1: array<string, int>}
     */
    /**
     * @param array<int, array<int, int>> $shipPools
     * @return array{0: array<int, bool>, 1: array<string, int>}
     */
    private function runPhaseMotorship(array $excludedFromCruiseFile, bool $dryRun, array $shipPools): array
    {
        $usedReviewIds = [];
        $stats = ['ships' => 0, 'assigned' => 0, 'shortages' => 0];

        $shipIds = Motorships::query()
            ->orderBy('id')
            ->pluck('id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->values()
            ->all();

        $now = Carbon::now()->toDateTimeString();
        $insertRows = [];

        foreach ($shipIds as $shipId) {
            $stats['ships']++;
            $eligible = [];
            foreach ($shipPools[$shipId] ?? [] as $reviewId) {
                if (!isset($excludedFromCruiseFile[$reviewId])) {
                    $eligible[] = $reviewId;
                }
            }
            shuffle($eligible);
            $pickedIds = array_slice($eligible, 0, min(self::MOTORSHIP_BINDINGS_LIMIT, count($eligible)));

            if (count($pickedIds) < self::MOTORSHIP_BINDINGS_LIMIT) {
                $stats['shortages']++;
            }

            foreach ($pickedIds as $reviewId) {
                $usedReviewIds[$reviewId] = true;
                if (!$dryRun) {
                    $insertRows[] = [
                        'review_id' => $reviewId,
                        'entity_type' => ReviewsWidget::ENTITY_MOTORSHIP,
                        'entity_id' => $shipId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            $stats['assigned'] += count($pickedIds);
        }

        if (!$dryRun && $insertRows !== []) {
            foreach (array_chunk($insertRows, 500) as $chunk) {
                DB::table('zen_reviews_bindings')->insert($chunk);
            }
        }

        return [$usedReviewIds, $stats];
    }

    /**
     * @param array<int, array<string, mixed>> $landingRows
     * @param array<int, bool> $excludedFromCruiseFile
     * @param array<int, bool> $usedReviewIds
     * @return array{0: array<string, int>, 1: array<int, array<string, mixed>>}
     */
    private function runPhaseLanding(
        array $landingRows,
        array $excludedFromCruiseFile,
        array $usedReviewIds,
        bool $dryRun
    ): array {
        $generalPool = $this->buildGeneralPool($excludedFromCruiseFile, $usedReviewIds);
        $generalIndex = 0;
        $shortages = [];
        $stats = [
            'requested' => 0,
            'assigned' => 0,
        ];

        $now = Carbon::now()->toDateTimeString();
        $insertRows = [];

        foreach ($landingRows as $row) {
            $entityType = $row['target']['entity_type'];
            $entityId = (int) $row['target']['entity_id'];
            $need = (int) $row['count'];
            $assigned = 0;
            $stats['requested'] += $need;

            while ($assigned < $need && $generalIndex < count($generalPool)) {
                $reviewId = (int) $generalPool[$generalIndex];
                $generalIndex++;
                if (isset($usedReviewIds[$reviewId])) {
                    continue;
                }

                $usedReviewIds[$reviewId] = true;
                if (!$dryRun) {
                    $insertRows[] = [
                        'review_id' => $reviewId,
                        'entity_type' => $entityType,
                        'entity_id' => $entityId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                $assigned++;
                $stats['assigned']++;
            }

            if ($assigned < $need) {
                $shortages[] = [
                    'line' => $row['line'],
                    'page_type' => $row['page_type'],
                    'slug_or_id' => $row['slug_or_id'],
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'requested' => $need,
                    'assigned' => $assigned,
                    'missing' => $need - $assigned,
                ];
            }
        }

        if (!$dryRun && $insertRows !== []) {
            foreach (array_chunk($insertRows, 500) as $chunk) {
                DB::table('zen_reviews_bindings')->insert($chunk);
            }
        }

        return [$stats, $shortages];
    }

    /**
     * @param array<int, bool> $excludedFromCruiseFile
     * @param array<int, bool> $usedReviewIds
     * @return array<int, int>
     */
    private function buildGeneralPool(array $excludedFromCruiseFile, array $usedReviewIds): array
    {
        $pool = [];
        $reviews = ZenReview::query()
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->pluck('id');

        foreach ($reviews as $reviewId) {
            $reviewId = (int) $reviewId;
            if (isset($excludedFromCruiseFile[$reviewId]) || isset($usedReviewIds[$reviewId])) {
                continue;
            }
            $pool[] = $reviewId;
        }

        return $pool;
    }

    private function printReport(
        array $resolveErrors,
        array $mergeWarnings,
        array $shortages,
        array $cruiseStats,
        array $motorshipStats,
        bool $dryRun
    ): void {
        $this->info('--- Итоговый отчёт ---');
        $this->line('Режим: ' . ($dryRun ? 'dry-run' : 'apply'));

        if ($mergeWarnings) {
            $this->warn('Слияние дубликатов цели в CSV: ' . count($mergeWarnings));
        }

        if ($resolveErrors) {
            $this->warn('Ошибки резолва посадочных: ' . count($resolveErrors));
        }

        if ($cruiseStats['short_checkins'] > 0) {
            $this->warn('Заездов с менее чем ' . CruiseReviewAssignments::REVIEWS_PER_CHECKIN . ' отзывами: '
                . $cruiseStats['short_checkins']);
        }

        if ($motorshipStats['shortages'] > 0) {
            $this->warn('Теплоходов с недобором (<' . self::MOTORSHIP_BINDINGS_LIMIT . '): '
                . $motorshipStats['shortages']);
        }

        if ($shortages === []) {
            $this->info('Недоборов по посадочным нет.');
        } else {
            $this->warn('Посадочных с недобором: ' . count($shortages));
            foreach (array_slice($shortages, 0, 15) as $item) {
                $this->line(sprintf(
                    '  line=%d %s:%s need=%d assigned=%d',
                    $item['line'],
                    $item['entity_type'],
                    $item['entity_id'],
                    $item['requested'],
                    $item['assigned']
                ));
            }
        }
    }
}
