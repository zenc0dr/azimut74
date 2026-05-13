<?php namespace Mcmraak\Rivercrs\Classes;

use Carbon\Carbon;
use Mcmraak\Rivercrs\Models\Cruises;
use Mcmraak\Rivercrs\Models\Motorships;
use Mcmraak\Rivercrs\Models\Transit;
use Zen\Reviews\Models\Binding;
use Zen\Reviews\Models\Review;

class ReviewsWidget
{
    const ENTITY_CRUISE = 'cruise';
    const ENTITY_TRANSIT = 'transit';
    const ENTITY_MOTORSHIP = 'motorship';

    /** Порядок и подписи оценок для публичного виджета (без reviews.azimut). */
    private const RATING_DEFINITIONS = [
        'cabin' => 'Каюта',
        'food' => 'Питание',
        'service' => 'Сервис',
        'tours' => 'Экскурсии',
        'anim_on_board' => 'Анимация',
        'cruise' => 'Отдых в целом',
    ];

    public static function detectEntityType($model)
    {
        if ($model instanceof Cruises) {
            return self::ENTITY_CRUISE;
        }

        if ($model instanceof Transit) {
            return self::ENTITY_TRANSIT;
        }

        if ($model instanceof Motorships) {
            return self::ENTITY_MOTORSHIP;
        }

        return null;
    }

    public static function getBindings($entityType, $entityId)
    {
        return Binding::with('review')
            ->where('entity_type', $entityType)
            ->where('entity_id', (int) $entityId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public static function getBoundReviews($entityType, $entityId)
    {
        return self::getBindings($entityType, $entityId)
            ->pluck('review')
            ->filter(function ($review) {
                return $review !== null;
            })
            ->values();
    }

    public static function formatReview(Review $review)
    {
        $form = self::extractForm($review);
        $text = trim((string) ($form['reviews_text'] ?? ''));

        $out = [
            'id' => (int) $review->id,
            'name' => (string) ($form['name'] ?? $review->name ?? 'Без имени'),
            'ship_id' => isset($form['ship_id']) ? (int) $form['ship_id'] : null,
            'ship_name' => (string) ($form['ship_name'] ?? ''),
            'text' => $text,
            'date' => $review->created_at ? Carbon::parse($review->created_at)->format('d.m.Y') : '',
        ];

        $trip = self::formatTripDateForWidget($form['trip_date'] ?? null);
        if ($trip !== null) {
            $out['trip_date'] = $trip;
        }

        $expRestLabel = self::formatExpRestForWidget($form['exp_rest'] ?? null);
        if ($expRestLabel !== null) {
            $out['exp_rest'] = $expRestLabel;
        }

        $ratings = self::buildPublicRatings($form);
        if ($ratings !== []) {
            $out['ratings'] = $ratings;
        }

        return $out;
    }

    public static function getShipOptions()
    {
        return Motorships::orderBy('sort_order')->lists('name', 'id');
    }

    /**
     * Базовый запрос отзывов для подгрузки (исключённые id + опционально теплоход).
     *
     * @param mixed $excludedIds
     * @param mixed $shipId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function moreReviewsQuery($excludedIds = [], $shipId = null)
    {
        $query = Review::query()->orderBy('created_at', 'desc');

        $excludedIds = array_values(array_filter(array_map('intval', (array) $excludedIds)));
        if ($excludedIds) {
            $query->whereNotIn('id', $excludedIds);
        }

        if ($shipId) {
            $shipId = (int) $shipId;
            // Граница числа обязательна: иначе ship_id=1 совпадает с 12, 21, 101 и т.д.
            $regexp = '"ship_id"[[:space:]]*:[[:space:]]*"?'
                . preg_quote((string) $shipId, '/')
                . '"?([^0-9]|$)';
            $query->whereRaw('data REGEXP ?', [$regexp]);
        }

        return $query;
    }

    /**
     * Сколько отзывов ещё доступно для подгрузки с учётом исключений и фильтра по судну.
     *
     * @param mixed $excludedIds
     * @param mixed $shipId
     */
    public static function countMoreReviews($excludedIds = [], $shipId = null): int
    {
        return (int) self::moreReviewsQuery($excludedIds, $shipId)->count();
    }

    public static function getMoreReviews($excludedIds = [], $shipId = null, $limit = 5)
    {
        return self::moreReviewsQuery($excludedIds, $shipId)
            ->take((int) $limit)
            ->get();
    }

    public static function extractForm(Review $review)
    {
        $data = $review->data ?: [];

        if (isset($data['form']) && is_array($data['form'])) {
            return $data['form'];
        }

        return is_array($data) ? $data : [];
    }

    /**
     * @param mixed $raw
     */
    private static function formatTripDateForWidget($raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)->format('d.m.Y');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Текст для поля «сколько раз отдыхали на теплоходе» (как в zen/reviews форме).
     *
     * @param mixed $raw
     */
    private static function formatExpRestForWidget($raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (is_numeric($raw)) {
            $n = (int) $raw;
            if ($n === 1) {
                return 'Первый раз';
            }
            if ($n === 2) {
                return 'Второй раз';
            }
            if ($n === 3) {
                return 'Три и более раз';
            }
        }

        $s = trim((string) $raw);

        return $s !== '' ? $s : null;
    }

    /**
     * @param mixed $value
     */
    private static function normalizeRatingValue($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        $n = (int) round((float) $value);

        if ($n < 1 || $n > 5) {
            return null;
        }

        return $n;
    }

    private static function buildPublicRatings(array $form): array
    {
        $reviews = $form['reviews'] ?? [];
        if (!is_array($reviews)) {
            return [];
        }

        $out = [];
        foreach (self::RATING_DEFINITIONS as $key => $label) {
            $val = self::normalizeRatingValue($reviews[$key] ?? null);
            if ($val !== null) {
                $out[] = [
                    'key' => $key,
                    'label' => $label,
                    'value' => $val,
                ];
            }
        }

        return $out;
    }
}
