<?php namespace Mcmraak\Rivercrs\Classes;

use Mcmraak\Rivercrs\Models\Cruises;
use Mcmraak\Rivercrs\Models\Motorships;
use Mcmraak\Rivercrs\Models\Transit;

/**
 * Парсинг и резолв эталонного CSV распределения отзывов (задача 0050).
 */
class ReviewsDistributionCsv
{
    public const INDEX_CRUISE_ID = 2;

    public function resolveCsvPath($path): ?string
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

    /**
     * @return array<int, array<string, mixed>>
     */
    public function parseCsv($csvPath): array
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

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array{0: array, 1: array}
     */
    public function resolveTargets(array $rows): array
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
                    'url' => $row['url'] ?? '',
                    'count' => (int) $row['count'],
                    'reason' => 'target-not-found',
                ];
            }
        }

        return [$resolved, $errors];
    }

    /**
     * @param array<int, array<string, mixed>> $resolvedRows
     * @return array{0: array, 1: array}
     */
    public function mergeResolvedRowsByTarget(array $resolvedRows): array
    {
        $merged = [];
        $keyToIndex = [];
        $warnings = [];

        foreach ($resolvedRows as $row) {
            $entityType = $row['target']['entity_type'];
            $entityId = (int) $row['target']['entity_id'];
            $key = $entityType . ':' . $entityId;

            if (!isset($keyToIndex[$key])) {
                $keyToIndex[$key] = count($merged);
                $row['_merge_source_lines'] = [$row['line']];
                $merged[] = $row;
                continue;
            }

            $idx = $keyToIndex[$key];
            $merged[$idx]['_merge_source_lines'][] = $row['line'];
            if ((int) $row['count'] > (int) $merged[$idx]['count']) {
                $merged[$idx]['count'] = (int) $row['count'];
            }
        }

        foreach ($merged as $idx => $row) {
            $lines = $row['_merge_source_lines'];
            unset($merged[$idx]['_merge_source_lines']);
            $merged[$idx]['csv_lines'] = implode(',', $lines);
            if (count($lines) > 1) {
                $warnings[] = [
                    'entity_type' => $row['target']['entity_type'],
                    'entity_id' => (int) $row['target']['entity_id'],
                    'lines' => $lines,
                    'count' => (int) $row['count'],
                ];
            }
        }

        return [$merged, $warnings];
    }
}
