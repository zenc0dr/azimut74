<?php namespace Mcmraak\Rivercrs\Classes;

/**
 * Фиксированные 3 случайных отзыва на карточку бронирования (/cruise/:checkin_id).
 * Хранятся в storage, не в zen_reviews_bindings.
 */
class CruiseReviewAssignments
{
    public const STORAGE_FILE = 'cruise_review_assignments.json';

    public const REVIEWS_PER_CHECKIN = 3;

    public static function storagePath(): string
    {
        return storage_path('app/' . self::STORAGE_FILE);
    }

    /**
     * @return array<string, array<int, int>> checkin_id => [review_id, ...]
     */
    public static function load(): array
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

        return is_array($data) ? $data : [];
    }

    /**
     * @param array<string|int, array<int, int>> $assignments
     */
    public static function save(array $assignments): void
    {
        $normalized = [];
        foreach ($assignments as $checkinId => $reviewIds) {
            $normalized[(string) (int) $checkinId] = array_values(array_map('intval', (array) $reviewIds));
        }

        $dir = dirname(self::storagePath());
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(
            self::storagePath(),
            json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }

    /**
     * @return array<int, int>
     */
    public static function getReviewIdsForCheckin(int $checkinId): array
    {
        $data = self::load();
        $key = (string) $checkinId;
        if (!isset($data[$key]) || !is_array($data[$key])) {
            return [];
        }

        return array_values(array_filter(array_map('intval', $data[$key])));
    }

    /**
     * Все review_id из файла (для исключения из фаз теплоходов и посадочных).
     *
     * @param array<string, array<int, int>>|null $assignments
     * @return array<int, bool>
     */
    public static function excludedReviewIdMap($assignments = null): array
    {
        $assignments = $assignments ?? self::load();
        $out = [];
        foreach ($assignments as $reviewIds) {
            foreach ((array) $reviewIds as $reviewId) {
                $reviewId = (int) $reviewId;
                if ($reviewId > 0) {
                    $out[$reviewId] = true;
                }
            }
        }

        return $out;
    }
}
