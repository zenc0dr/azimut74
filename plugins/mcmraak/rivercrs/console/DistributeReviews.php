<?php namespace Mcmraak\Rivercrs\Console;

use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;
use Mcmraak\Rivercrs\Classes\ReviewsWidget;
use Mcmraak\Rivercrs\Models\Cruises;
use Mcmraak\Rivercrs\Models\Motorships;
use Mcmraak\Rivercrs\Models\Transit;
use Symfony\Component\Console\Input\InputOption;
use Zen\Reviews\Models\Review as ZenReview;

class DistributeReviews extends Command
{
    protected $name = 'rivercrs:distribute-reviews';
    protected $description = 'Автоматическое распределение отзывов по CSV (index/cruise_menu/transit/motorship)';

    private const INDEX_CRUISE_ID = 2;

    protected function getOptions()
    {
        return [
            [
                'csv',
                null,
                InputOption::VALUE_OPTIONAL,
                'Путь к CSV (абсолютный или относительно base_path). По умолчанию — файл в storage (см. docker-compose: ./storage → контейнер).',
                'storage/app/distribution_of_reviews.csv',
            ],
            [
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Проверка без изменений в БД.',
                null,
            ],
        ];
    }

    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');
        $csvPath = $this->resolveCsvPath((string) $this->option('csv'));

        if (!$csvPath) {
            $this->error('CSV файл не найден. Проверьте опцию --csv.');
            return 1;
        }

        $rows = $this->parseCsv($csvPath);
        if (empty($rows)) {
            $this->error('В CSV не найдено валидных строк для распределения.');
            return 1;
        }

        $this->info('CSV: ' . $csvPath);
        $this->info('Строк для обработки: ' . count($rows));

        [$resolvedRows, $resolveErrors] = $this->resolveTargets($rows);
        $this->info('Успешно резолвлено строк: ' . count($resolvedRows));

        if ($resolveErrors) {
            $this->warn('Строк с ошибками резолва: ' . count($resolveErrors));
        }

        [$generalPool, $shipPools] = $this->buildReviewPools();
        $this->info('Отзывы в общем пуле: ' . count($generalPool));
        $this->info('Теплоходов с отдельным пулом: ' . count($shipPools));

        [$allocations, $shortages, $usageStats] = $this->buildAllocations($resolvedRows, $generalPool, $shipPools);

        if (!$dryRun) {
            $this->info('Очищаю zen_reviews_bindings...');
            DB::table('zen_reviews_bindings')->truncate();
            $this->persistAllocations($allocations);
            $this->info('Новые привязки записаны: ' . count($allocations));
        } else {
            $this->info('DRY-RUN: изменения в БД не выполнялись.');
            $this->info('Планируемых привязок: ' . count($allocations));
        }

        $this->printReport($resolveErrors, $shortages, $usageStats, count($allocations), $dryRun);
        return 0;
    }

    private function resolveCsvPath($path)
    {
        if (!$path) {
            return null;
        }

        $candidates = [];

        if (strpos($path, '/') === 0) {
            $candidates[] = $path;
        } else {
            $candidates[] = base_path($path);
            $candidates[] = base_path('../' . ltrim($path, '/'));
        }

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function parseCsv($csvPath)
    {
        $handle = fopen($csvPath, 'rb');
        if ($handle === false) {
            return [];
        }

        $header = fgetcsv($handle);
        if (!$header || count($header) < 4) {
            fclose($handle);
            return [];
        }

        $rows = [];
        $line = 1;
        while (($data = fgetcsv($handle)) !== false) {
            $line++;
            if (count($data) < 4) {
                continue;
            }

            $pageType = trim((string) ($data[0] ?? ''));
            $slugOrId = trim((string) ($data[1] ?? ''));
            $title = trim((string) ($data[2] ?? ''));
            $count = (int) trim((string) ($data[3] ?? '0'));
            $url = trim((string) ($data[4] ?? ''));

            if (!$pageType || $count <= 0) {
                continue;
            }

            $rows[] = [
                'line' => $line,
                'page_type' => $pageType,
                'slug_or_id' => $slugOrId,
                'title' => $title,
                'count' => $count,
                'url' => $url,
            ];
        }

        fclose($handle);
        return $rows;
    }

    private function resolveTargets(array $rows)
    {
        $resolved = [];
        $errors = [];
        $indexCruiseExists = Cruises::where('id', self::INDEX_CRUISE_ID)->exists();

        foreach ($rows as $row) {
            $pageType = $row['page_type'];
            $slugOrId = $row['slug_or_id'];
            $target = null;

            if ($pageType === 'index') {
                if ($indexCruiseExists) {
                    $target = [
                        'entity_type' => ReviewsWidget::ENTITY_CRUISE,
                        'entity_id' => self::INDEX_CRUISE_ID,
                        'resolved_by' => 'index-fixed-id',
                    ];
                }
            } elseif ($pageType === 'cruise_menu') {
                $cruise = Cruises::where('slug', $slugOrId)->first();
                if ($cruise) {
                    $target = [
                        'entity_type' => ReviewsWidget::ENTITY_CRUISE,
                        'entity_id' => (int) $cruise->id,
                        'resolved_by' => 'cruise.slug',
                    ];
                }
            } elseif ($pageType === 'transit') {
                $transit = Transit::where('slug', $slugOrId)->first();
                if ($transit) {
                    $target = [
                        'entity_type' => ReviewsWidget::ENTITY_TRANSIT,
                        'entity_id' => (int) $transit->id,
                        'resolved_by' => 'transit.slug',
                    ];
                }
            } elseif ($pageType === 'motorship') {
                $motorshipId = (int) $slugOrId;
                if ($motorshipId > 0 && Motorships::where('id', $motorshipId)->exists()) {
                    $target = [
                        'entity_type' => ReviewsWidget::ENTITY_MOTORSHIP,
                        'entity_id' => $motorshipId,
                        'resolved_by' => 'motorship.id',
                    ];
                }
            }

            if ($target) {
                $row['target'] = $target;
                $resolved[] = $row;
            } else {
                $errors[] = [
                    'line' => $row['line'],
                    'page_type' => $pageType,
                    'slug_or_id' => $slugOrId,
                    'title' => $row['title'],
                    'reason' => 'target-not-found',
                ];
            }
        }

        return [$resolved, $errors];
    }

    private function buildReviewPools()
    {
        $reviews = ZenReview::query()
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $generalPool = [];
        $shipPools = [];

        foreach ($reviews as $review) {
            $reviewId = (int) $review->id;
            $generalPool[] = $reviewId;

            $form = ReviewsWidget::extractForm($review);
            $shipId = isset($form['ship_id']) ? (int) $form['ship_id'] : 0;
            if ($shipId > 0) {
                if (!isset($shipPools[$shipId])) {
                    $shipPools[$shipId] = [];
                }
                $shipPools[$shipId][] = $reviewId;
            }
        }

        return [$generalPool, $shipPools];
    }

    private function buildAllocations(array $resolvedRows, array $generalPool, array $shipPools)
    {
        $allocations = [];
        $shortages = [];
        $usedReviewIds = [];
        $usageStats = [
            'requested' => 0,
            'assigned' => 0,
            'by_type' => [
                ReviewsWidget::ENTITY_CRUISE => 0,
                ReviewsWidget::ENTITY_TRANSIT => 0,
                ReviewsWidget::ENTITY_MOTORSHIP => 0,
            ],
        ];

        $generalIndex = 0;
        $shipIndex = [];

        foreach ($resolvedRows as $row) {
            $entityType = $row['target']['entity_type'];
            $entityId = (int) $row['target']['entity_id'];
            $need = (int) $row['count'];
            $assigned = 0;
            $usageStats['requested'] += $need;

            if ($entityType === ReviewsWidget::ENTITY_MOTORSHIP) {
                $pool = $shipPools[$entityId] ?? [];
                if (!isset($shipIndex[$entityId])) {
                    $shipIndex[$entityId] = 0;
                }

                while ($assigned < $need && $shipIndex[$entityId] < count($pool)) {
                    $reviewId = (int) $pool[$shipIndex[$entityId]];
                    $shipIndex[$entityId]++;
                    if (isset($usedReviewIds[$reviewId])) {
                        continue;
                    }

                    $usedReviewIds[$reviewId] = true;
                    $allocations[] = [
                        'review_id' => $reviewId,
                        'entity_type' => $entityType,
                        'entity_id' => $entityId,
                    ];
                    $assigned++;
                    $usageStats['assigned']++;
                    $usageStats['by_type'][$entityType]++;
                }
            } else {
                while ($assigned < $need && $generalIndex < count($generalPool)) {
                    $reviewId = (int) $generalPool[$generalIndex];
                    $generalIndex++;
                    if (isset($usedReviewIds[$reviewId])) {
                        continue;
                    }

                    $usedReviewIds[$reviewId] = true;
                    $allocations[] = [
                        'review_id' => $reviewId,
                        'entity_type' => $entityType,
                        'entity_id' => $entityId,
                    ];
                    $assigned++;
                    $usageStats['assigned']++;
                    $usageStats['by_type'][$entityType]++;
                }
            }

            if ($assigned < $need) {
                $shortages[] = [
                    'line' => $row['line'],
                    'page_type' => $row['page_type'],
                    'slug_or_id' => $row['slug_or_id'],
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'title' => $row['title'],
                    'requested' => $need,
                    'assigned' => $assigned,
                    'missing' => $need - $assigned,
                ];
            }
        }

        return [$allocations, $shortages, $usageStats];
    }

    private function persistAllocations(array $allocations)
    {
        if (empty($allocations)) {
            return;
        }

        $now = Carbon::now()->toDateTimeString();
        $chunks = array_chunk($allocations, 500);
        foreach ($chunks as $chunk) {
            $rows = [];
            foreach ($chunk as $item) {
                $rows[] = [
                    'review_id' => (int) $item['review_id'],
                    'entity_type' => $item['entity_type'],
                    'entity_id' => (int) $item['entity_id'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('zen_reviews_bindings')->insert($rows);
        }
    }

    private function printReport(array $resolveErrors, array $shortages, array $usageStats, int $allocationCount, bool $dryRun)
    {
        $this->info('--- Итоговый отчет ---');
        $this->line('Режим: ' . ($dryRun ? 'dry-run' : 'apply'));
        $this->line('Запрошено отзывов: ' . $usageStats['requested']);
        $this->line('Назначено отзывов: ' . $usageStats['assigned']);
        $this->line('Фактических привязок: ' . $allocationCount);
        $this->line('Назначено cruise: ' . (int) $usageStats['by_type'][ReviewsWidget::ENTITY_CRUISE]);
        $this->line('Назначено transit: ' . (int) $usageStats['by_type'][ReviewsWidget::ENTITY_TRANSIT]);
        $this->line('Назначено motorship: ' . (int) $usageStats['by_type'][ReviewsWidget::ENTITY_MOTORSHIP]);

        if ($resolveErrors) {
            $this->warn('Ошибки резолва (' . count($resolveErrors) . '):');
            foreach (array_slice($resolveErrors, 0, 20) as $error) {
                $this->line(sprintf(
                    '  line=%d type=%s key=%s reason=%s',
                    $error['line'],
                    $error['page_type'],
                    $error['slug_or_id'] ?: '-',
                    $error['reason']
                ));
            }
            if (count($resolveErrors) > 20) {
                $this->line('  ... и еще ' . (count($resolveErrors) - 20) . ' строк');
            }
        }

        if ($shortages) {
            $this->warn('Страницы с недобором (' . count($shortages) . '):');
            foreach (array_slice($shortages, 0, 30) as $item) {
                $this->line(sprintf(
                    '  line=%d %s:%s need=%d assigned=%d missing=%d',
                    $item['line'],
                    $item['entity_type'],
                    $item['entity_id'],
                    $item['requested'],
                    $item['assigned'],
                    $item['missing']
                ));
            }
            if (count($shortages) > 30) {
                $this->line('  ... и еще ' . (count($shortages) - 30) . ' строк');
            }
        } else {
            $this->info('Недоборов нет.');
        }
    }
}
