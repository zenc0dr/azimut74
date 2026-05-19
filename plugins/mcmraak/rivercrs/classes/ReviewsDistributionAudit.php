<?php namespace Mcmraak\Rivercrs\Classes;

use DB;
use Zen\Reviews\Models\Review as ZenReview;

/**
 * Проверка фактического распределения отзывов (bindings + fallback как на сайте).
 */
class ReviewsDistributionAudit
{
    /** Ожидаемое число авто-привязок на теплоход (rivercrs:distribute-reviews). */
    private const MOTORSHIP_AUTO_BINDINGS = 3;

    /** @var array<int, ZenReview> */
    private $reviewsById = [];

    /** @var array<int, bool> review_id занят на cruise/transit */
    private $blockedForMotorship = [];

    /** @var array<string, array<int, int[]>> entity_key => review ids */
    private $bindingsByEntity = [];

    /** @var array<int, string> review_id => entity_key */
    private $reviewToEntity = [];

    /** @var array<int, int> */
    private $generalPoolSize = ['count' => 0];

    /** @var array<int, int> ship_id => count */
    private $shipPoolCounts = [];

    public function buildReport(array $mergedRows, array $resolveErrors): array
    {
        $this->loadBindingsIndex();
        $this->loadReviewPools();

        $reportRows = [];
        $stats = [
            'targets_total' => 0,
            'targets_ok' => 0,
            'targets_problem' => 0,
            'resolve_errors' => count($resolveErrors),
            'duplicate_bindings_in_db' => 0,
            'reviews_with_multiple_bindings' => [],
        ];

        $dupIds = $this->findDuplicateReviewIdsInBindings();
        $stats['duplicate_bindings_in_db'] = count($dupIds);
        $stats['reviews_with_multiple_bindings'] = $dupIds;

        foreach ($mergedRows as $row) {
            $reportRows[] = $this->auditTargetRow($row);
        }

        foreach ($resolveErrors as $error) {
            $reportRows[] = $this->auditResolveErrorRow($error);
        }

        foreach ($reportRows as $r) {
            if (($r['row_kind'] ?? '') !== 'target') {
                continue;
            }
            $stats['targets_total']++;
            if (($r['status'] ?? '') === 'OK') {
                $stats['targets_ok']++;
            } else {
                $stats['targets_problem']++;
            }
        }

        $stats['bindings_total'] = (int) DB::table('zen_reviews_bindings')->count();
        $stats['general_pool_reviews'] = $this->generalPoolSize['count'];
        $stats['ship_pool_ships'] = count($this->shipPoolCounts);

        return [
            'rows' => $reportRows,
            'summary' => $stats,
        ];
    }

    private function loadBindingsIndex(): void
    {
        $bindings = DB::table('zen_reviews_bindings')
            ->select(['review_id', 'entity_type', 'entity_id'])
            ->get();

        foreach ($bindings as $b) {
            $reviewId = (int) $b->review_id;
            $key = $b->entity_type . ':' . (int) $b->entity_id;
            if (!isset($this->bindingsByEntity[$key])) {
                $this->bindingsByEntity[$key] = [];
            }
            $this->bindingsByEntity[$key][] = $reviewId;
            $this->reviewToEntity[$reviewId] = $key;
            if ($b->entity_type !== ReviewsWidget::ENTITY_MOTORSHIP) {
                $this->blockedForMotorship[$reviewId] = true;
            }
        }
    }

    private function loadReviewPools(): void
    {
        $general = 0;
        $ships = [];

        foreach (ZenReview::query()->cursor() as $review) {
            $form = ReviewsWidget::extractForm($review);
            $shipId = ReviewsWidget::normalizeShipId($form['ship_id'] ?? null);
            if ($shipId > 0) {
                $ships[$shipId] = ($ships[$shipId] ?? 0) + 1;
            } else {
                $general++;
            }
        }

        $this->generalPoolSize['count'] = $general;
        $this->shipPoolCounts = $ships;
    }

    /**
     * @return array<int, int>
     */
    private function findDuplicateReviewIdsInBindings(): array
    {
        $rows = DB::table('zen_reviews_bindings')
            ->select('review_id', DB::raw('COUNT(*) as c'))
            ->groupBy('review_id')
            ->having('c', '>', 1)
            ->pluck('review_id')
            ->all();

        return array_map('intval', $rows);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function auditTargetRow(array $row): array
    {
        $target = $row['target'];
        $entityType = $target['entity_type'];
        $entityId = (int) $target['entity_id'];
        $pageType = $row['page_type'];
        $isStaticSeo = in_array($pageType, ['index', 'cruise_menu', 'transit'], true);
        $expected = (int) $row['count'];
        if ($entityType === ReviewsWidget::ENTITY_MOTORSHIP) {
            $expected = self::MOTORSHIP_AUTO_BINDINGS;
        }
        $key = $entityType . ':' . $entityId;

        $reviewIds = $this->bindingsByEntity[$key] ?? [];
        $bindingCount = count($reviewIds);

        $issues = [];
        $shipMatch = 'н/д';
        $uniqueAmongCruises = 'н/д';
        $wrongPool = [];

        foreach ($reviewIds as $reviewId) {
            $review = $this->getReview($reviewId);
            if (!$review) {
                $issues[] = 'отзыв id=' . $reviewId . ' не найден';
                continue;
            }
            $form = ReviewsWidget::extractForm($review);
            $shipId = ReviewsWidget::normalizeShipId($form['ship_id'] ?? null);

            if ($entityType === ReviewsWidget::ENTITY_MOTORSHIP) {
                if ($shipId !== $entityId) {
                    $issues[] = 'ship_id=' . $shipId . ' ≠ теплоход ' . $entityId;
                }
            } elseif ($shipId > 0 && !$isStaticSeo) {
                $wrongPool[] = $reviewId;
            }
        }

        if ($entityType === ReviewsWidget::ENTITY_MOTORSHIP) {
            $shipMatch = empty($issues) && $bindingCount > 0 ? 'да' : ($bindingCount === 0 ? 'нет привязок' : 'нет');
        }

        if ($entityType === ReviewsWidget::ENTITY_CRUISE) {
            $uniqueAmongCruises = $this->checkUniqueAmongEntityType($reviewIds, ReviewsWidget::ENTITY_CRUISE, $key)
                ? 'да'
                : 'нет';
        }

        if ($wrongPool !== []) {
            $issues[] = 'отзыв с ship_id в общем разделе: ' . implode(',', $wrongPool);
        }

        [$widgetCount, $widgetSource, $fallbackIds] = $this->resolveWidgetDefaults(
            $entityType,
            $entityId,
            $reviewIds
        );

        if ($entityType === ReviewsWidget::ENTITY_MOTORSHIP && $bindingCount === 0 && $fallbackIds !== []) {
            $fallbackMismatch = false;
            foreach ($fallbackIds as $reviewId) {
                $review = $this->getReview($reviewId);
                if (!$review) {
                    continue;
                }
                $form = ReviewsWidget::extractForm($review);
                $shipId = ReviewsWidget::normalizeShipId($form['ship_id'] ?? null);
                if ($shipId !== $entityId) {
                    $fallbackMismatch = true;
                    $issues[] = 'fallback ship_id=' . $shipId . ' ≠ ' . $entityId;
                }
            }
            $shipMatch = $fallbackMismatch ? 'нет' : 'да (fallback)';
        }

        $status = 'OK';

        if ($isStaticSeo) {
            if ($bindingCount < 1 && $widgetCount < 1) {
                $status = 'Нет отзывов';
                $issues[] = 'нет SEO-привязок (cruise/transit не трогает автораспределение)';
            }
        } elseif ($entityType === ReviewsWidget::ENTITY_MOTORSHIP) {
            $eligible = $this->countEligibleReviewsForShip($entityId);
            $need = min(self::MOTORSHIP_AUTO_BINDINGS, $eligible);
            if ($bindingCount < 1 && $widgetCount < 1) {
                $status = 'Нет отзывов';
                $issues[] = 'нет привязок и нет fallback';
            } elseif ($bindingCount < $need) {
                if ($bindingCount < 1 && $widgetSource === 'fallback') {
                    $status = 'Только fallback';
                    $issues[] = 'нужно привязать до ' . $need . ', на сайте fallback';
                } else {
                    $status = 'Недобор';
                    $issues[] = 'привязано ' . $bindingCount . ' из ' . $need
                        . ' (свободных отзывов судна: ' . $eligible . ')';
                }
            }
        } elseif ($bindingCount < $expected) {
            if ($widgetCount < 1) {
                $status = 'Нет отзывов';
                $issues[] = 'привязок ' . $bindingCount . ', нужно ' . $expected;
            } else {
                $status = 'Недобор';
                $issues[] = 'привязано ' . $bindingCount . ' из ' . $expected;
            }
        } elseif ($bindingCount < 1 && $widgetCount < 1) {
            $status = 'Нет отзывов';
        }

        if ($issues !== [] && $status === 'OK' && !$isStaticSeo) {
            $status = 'Предупреждение';
        }

        $displayIds = $bindingCount > 0 ? $reviewIds : $fallbackIds;

        return [
            'row_kind' => 'target',
            'status' => $status,
            'issues' => implode('; ', array_unique($issues)),
            'page_type' => $row['page_type'],
            'slug_or_id' => $row['slug_or_id'],
            'title' => $row['title'],
            'url' => $row['url'] ?? '',
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'csv_lines' => $row['csv_lines'] ?? (string) $row['line'],
            'expected_count' => $expected,
            'binding_count' => $bindingCount,
            'widget_count' => $widgetCount,
            'widget_source' => $widgetSource,
            'review_ids' => implode(',', $displayIds),
            'ship_match' => $shipMatch,
            'unique_among_cruises' => $uniqueAmongCruises,
        ];
    }

    /**
     * @param array<string, mixed> $error
     */
    private function auditResolveErrorRow(array $error): array
    {
        return [
            'row_kind' => 'error',
            'status' => 'Цель не найдена',
            'issues' => $error['reason'] ?? 'target-not-found',
            'page_type' => $error['page_type'],
            'slug_or_id' => $error['slug_or_id'],
            'title' => $error['title'],
            'url' => $error['url'] ?? '',
            'entity_type' => '',
            'entity_id' => '',
            'csv_lines' => (string) $error['line'],
            'expected_count' => (int) ($error['count'] ?? 0),
            'binding_count' => 0,
            'widget_count' => 0,
            'widget_source' => 'none',
            'review_ids' => '',
            'ship_match' => 'н/д',
            'unique_among_cruises' => 'н/д',
        ];
    }

    /**
     * @param int[] $reviewIds
     * @return array{0: int, 1: string, 2: int[]}
     */
    private function resolveWidgetDefaults(string $entityType, int $entityId, array $reviewIds): array
    {
        if ($reviewIds !== []) {
            return [count($reviewIds), 'bindings', $reviewIds];
        }

        if ($entityType === ReviewsWidget::ENTITY_MOTORSHIP) {
            $fallback = ReviewsWidget::getLatestReviewsForShip($entityId, 3)
                ->pluck('id')
                ->map(function ($id) {
                    return (int) $id;
                })
                ->values()
                ->all();
            if ($fallback !== []) {
                return [count($fallback), 'fallback', $fallback];
            }
        }

        return [0, 'none', []];
    }

    /**
     * @param int[] $reviewIds
     */
    private function checkUniqueAmongEntityType(array $reviewIds, string $entityType, string $currentKey): bool
    {
        foreach ($reviewIds as $reviewId) {
            $owner = $this->reviewToEntity[$reviewId] ?? null;
            if ($owner === null) {
                continue;
            }
            if ($owner === $currentKey) {
                continue;
            }
            [$type] = explode(':', $owner, 2);
            if ($type === $entityType) {
                return false;
            }
        }

        return true;
    }

    private function getReview(int $reviewId): ?ZenReview
    {
        if (!isset($this->reviewsById[$reviewId])) {
            $this->reviewsById[$reviewId] = ZenReview::find($reviewId);
        }

        return $this->reviewsById[$reviewId];
    }

    private function countEligibleReviewsForShip(int $shipId): int
    {
        $count = 0;
        foreach (ReviewsWidget::getLatestReviewsForShip($shipId, 500) as $review) {
            if (!isset($this->blockedForMotorship[(int) $review->id])) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param array<string, mixed> $summary
     * @return array<int, array<string, string>>
     */
    public function buildSummaryRows(array $summary): array
    {
        $dup = $summary['reviews_with_multiple_bindings'] ?? [];
        $dupText = $dup === [] ? 'нет' : implode(',', $dup);

        return [
            [
                'row_kind' => 'summary',
                'status' => ($summary['targets_problem'] ?? 0) === 0 && ($summary['resolve_errors'] ?? 0) === 0
                    ? 'Сводка: OK'
                    : 'Сводка: есть проблемы',
                'issues' => sprintf(
                    'целей с проблемами: %d из %d; ошибок резолва: %d; привязок в БД: %d; общий пул: %d; теплоходов в пулах: %d; дубликаты review_id: %s',
                    (int) ($summary['targets_problem'] ?? 0),
                    (int) ($summary['targets_total'] ?? 0),
                    (int) ($summary['resolve_errors'] ?? 0),
                    (int) ($summary['bindings_total'] ?? 0),
                    (int) ($summary['general_pool_reviews'] ?? 0),
                    (int) ($summary['ship_pool_ships'] ?? 0),
                    $dupText
                ),
                'page_type' => '_summary',
                'slug_or_id' => '',
                'title' => 'Итог проверки',
                'url' => '',
                'entity_type' => '',
                'entity_id' => '',
                'csv_lines' => '',
                'expected_count' => '',
                'binding_count' => '',
                'widget_count' => '',
                'widget_source' => '',
                'review_ids' => '',
                'ship_match' => '',
                'unique_among_cruises' => '',
            ],
        ];
    }
}
