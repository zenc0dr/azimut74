<?php namespace Mcmraak\Rivercrs\Console;

use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;
use Mcmraak\Rivercrs\Classes\ReviewsDistributionCsv;
use Mcmraak\Rivercrs\Classes\ReviewsWidget;
use Mcmraak\Rivercrs\Models\Motorships;
use Symfony\Component\Console\Input\InputOption;
use Zen\Reviews\Models\Review as ZenReview;

class DistributeReviews extends Command
{
    protected $name = 'rivercrs:distribute-reviews';

    protected $description = 'Обновляет привязки отзывов только для теплоходов (N свежих на судно). Cruise/transit/index не изменяет.';

    /** Сколько свежих отзывов класть на карточку теплохода. */
    public const MOTORSHIP_BINDINGS_LIMIT = 3;

    /** @var ReviewsDistributionCsv */
    private $distributionCsv;

    protected function getOptions()
    {
        return [
            [
                'csv',
                null,
                InputOption::VALUE_OPTIONAL,
                'Опционально: только теплоходы из motorship-строк CSV. Без файла — все суда из БД.',
                null,
            ],
            [
                'limit',
                null,
                InputOption::VALUE_OPTIONAL,
                'Максимум свежих отзывов на теплоход (по умолчанию ' . self::MOTORSHIP_BINDINGS_LIMIT . ').',
                (string) self::MOTORSHIP_BINDINGS_LIMIT,
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
        $this->distributionCsv = new ReviewsDistributionCsv();
        $dryRun = (bool) $this->option('dry-run');
        $limit = max(1, (int) $this->option('limit'));

        $shipIds = $this->resolveMotorshipIds();
        if ($shipIds === []) {
            $this->error('Не найдено ни одного теплохода для обработки.');
            return 1;
        }

        $blockedReviewIds = $this->loadBlockedReviewIdsForOtherEntities();

        $this->info('Режим: только motorship, cruise/transit/index не затрагиваются.');
        $this->info('Теплоходов к обработке: ' . count($shipIds));
        $this->info('Лимит свежих отзывов на судно: ' . $limit);
        $this->info('Отзывов, занятых на cruise/transit (нельзя переназначить): ' . count($blockedReviewIds));

        $deletedTotal = 0;
        $insertedTotal = 0;
        $shortages = [];

        foreach ($shipIds as $shipId) {
            $shipId = (int) $shipId;
            $reviews = ReviewsWidget::getLatestReviewsForShipUnblocked(
                $shipId,
                $limit,
                $blockedReviewIds
            );

            $pickedIds = $reviews->pluck('id')->map(function ($id) {
                return (int) $id;
            })->values()->all();

            if (!$dryRun) {
                $deleted = DB::table('zen_reviews_bindings')
                    ->where('entity_type', ReviewsWidget::ENTITY_MOTORSHIP)
                    ->where('entity_id', $shipId)
                    ->delete();
                $deletedTotal += $deleted;

                if ($pickedIds !== []) {
                    $this->insertMotorshipBindings($shipId, $pickedIds);
                    $insertedTotal += count($pickedIds);
                }
            }

            if (count($pickedIds) < $limit) {
                $eligible = $this->countEligibleReviewsForShip($shipId, $blockedReviewIds);
                $shortages[] = [
                    'ship_id' => $shipId,
                    'requested' => $limit,
                    'assigned' => count($pickedIds),
                    'missing' => $limit - count($pickedIds),
                    'eligible_in_db' => $eligible,
                    'review_ids' => implode(',', $pickedIds),
                ];
            }
        }

        if ($dryRun) {
            $this->info('DRY-RUN: изменения в БД не выполнялись.');
            $wouldInsert = 0;
            foreach ($shipIds as $shipId) {
                $reviews = ReviewsWidget::getLatestReviewsForShipUnblocked(
                    (int) $shipId,
                    $limit,
                    $blockedReviewIds
                );
                $wouldInsert += $reviews->count();
            }
            $this->info('Планируется привязок (всего): ' . $wouldInsert);
        } else {
            $this->info('Удалено старых motorship-привязок: ' . $deletedTotal);
            $this->info('Добавлено новых motorship-привязок: ' . $insertedTotal);
            $this->info('Всего привязок в БД: ' . (int) DB::table('zen_reviews_bindings')->count());
        }

        $this->printReport($shortages, $dryRun);

        return 0;
    }

    /**
     * Отзывы, уже привязанные к cruise/transit/index — для motorship не используем.
     *
     * @return array<int, bool> review_id => true
     */
    private function loadBlockedReviewIdsForOtherEntities(): array
    {
        $blocked = [];
        $rows = DB::table('zen_reviews_bindings')
            ->where('entity_type', '!=', ReviewsWidget::ENTITY_MOTORSHIP)
            ->pluck('review_id');

        foreach ($rows as $reviewId) {
            $blocked[(int) $reviewId] = true;
        }

        return $blocked;
    }

    /**
     * @return array<int, int>
     */
    private function resolveMotorshipIds(): array
    {
        $csvPath = $this->distributionCsv->resolveCsvPath((string) $this->option('csv'));
        if ($csvPath) {
            $this->info('CSV (фильтр теплоходов): ' . $csvPath);
            $rows = $this->distributionCsv->parseCsv($csvPath);
            [$resolved] = $this->distributionCsv->resolveTargets($rows);
            $ids = [];
            foreach ($resolved as $row) {
                if (($row['target']['entity_type'] ?? '') !== ReviewsWidget::ENTITY_MOTORSHIP) {
                    continue;
                }
                $ids[] = (int) $row['target']['entity_id'];
            }

            return array_values(array_unique($ids));
        }

        return Motorships::query()
            ->orderBy('id')
            ->pluck('id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->values()
            ->all();
    }

    /**
     * @param array<int, bool> $blockedReviewIds
     */
    private function countEligibleReviewsForShip(int $shipId, array $blockedReviewIds): int
    {
        $count = 0;
        $chunk = ReviewsWidget::getLatestReviewsForShip($shipId, 500);
        foreach ($chunk as $review) {
            $id = (int) $review->id;
            if (!isset($blockedReviewIds[$id])) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param array<int, int> $reviewIds
     */
    private function insertMotorshipBindings(int $shipId, array $reviewIds): void
    {
        $now = Carbon::now()->toDateTimeString();
        $rows = [];
        foreach ($reviewIds as $reviewId) {
            $rows[] = [
                'review_id' => (int) $reviewId,
                'entity_type' => ReviewsWidget::ENTITY_MOTORSHIP,
                'entity_id' => $shipId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('zen_reviews_bindings')->insert($rows);
    }

    private function printReport(array $shortages, bool $dryRun): void
    {
        $this->info('--- Итоговый отчёт ---');
        $this->line('Режим: ' . ($dryRun ? 'dry-run' : 'apply'));

        if ($shortages === []) {
            $this->info('На всех обработанных теплоходах набрано по ' . (int) $this->option('limit') . ' отзывов (или меньше нет в БД).');
            return;
        }

        $this->warn('Теплоходов с недобором: ' . count($shortages));
        foreach (array_slice($shortages, 0, 25) as $item) {
            $this->line(sprintf(
                '  motorship:%d need=%d assigned=%d eligible=%d ids=[%s]',
                $item['ship_id'],
                $item['requested'],
                $item['assigned'],
                $item['eligible_in_db'],
                $item['review_ids'] ?: '-'
            ));
        }
        if (count($shortages) > 25) {
            $this->line('  ... и ещё ' . (count($shortages) - 25));
        }
    }
}
