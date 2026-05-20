<?php namespace Mcmraak\Rivercrs\Classes;

/**
 * Три случайных отзыва для всех карточек бронирования (/cruise/:checkin_id).
 * Один набор на весь сайт, хранится в storage (не zen_reviews_bindings).
 */
class CruiseReviewAssignments
{
    public const STORAGE_FILE = 'cruise_review_assignments.json';

    public const GLOBAL_REVIEW_COUNT = 3;

    public static function storagePath(): string
    {
        return storage_path('app/' . self::STORAGE_FILE);
    }

    /**
     * @return array<int, int>
     */
    public static function getGlobalReviewIds(): array
    {
        $path = self::storagePath();
        if (!is_file($path)) {
            return [];
        }

        $raw = file_get_contents($path);
        if ($raw === false || $raw === '') {
            return [];
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return [];
        }

        if (isset($data['review_ids']) && is_array($data['review_ids'])) {
            return self::normalizeIdList($data['review_ids']);
        }

        // Старый формат: checkin_id => [ids] — берём первый непустой набор (все страницы показывали один пул)
        $first = null;
        foreach ($data as $value) {
            if (is_array($value) && $value !== []) {
                $first = self::normalizeIdList($value);
                break;
            }
        }

        return $first ?? [];
    }

    /**
     * @param array<int, int> $reviewIds
     */
    public static function saveReviewIds(array $reviewIds): void
    {
        $reviewIds = self::normalizeIdList($reviewIds);
        $payload = [
            'review_ids' => $reviewIds,
            'generated_at' => date('c'),
        ];

        $dir = dirname(self::storagePath());
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(
            self::storagePath(),
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }

    /**
     * @deprecated Используйте getGlobalReviewIds()
     * @return array<int, int>
     */
    public static function getReviewIdsForCheckin(int $checkinId): array
    {
        return self::getGlobalReviewIds();
    }

    /**
     * @param array<int, int>|null $reviewIds
     * @return array<int, bool>
     */
    public static function excludedReviewIdMap($reviewIds = null): array
    {
        $reviewIds = $reviewIds ?? self::getGlobalReviewIds();
        $out = [];
        foreach ($reviewIds as $reviewId) {
            if ($reviewId > 0) {
                $out[$reviewId] = true;
            }
        }

        return $out;
    }

    /**
     * @param mixed $list
     * @return array<int, int>
     */
    private static function normalizeIdList($list): array
    {
        $out = [];
        foreach ((array) $list as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $out[] = $id;
            }
        }

        return array_values(array_unique($out));
    }
}
