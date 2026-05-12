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

        $expRest = trim((string) ($form['exp_rest'] ?? ''));
        if ($expRest !== '') {
            $out['exp_rest'] = $expRest;
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

    public static function getMoreReviews($excludedIds = [], $shipId = null, $limit = 5)
    {
        $query = Review::query()->orderBy('created_at', 'desc');

        $excludedIds = array_values(array_filter(array_map('intval', (array) $excludedIds)));
        if ($excludedIds) {
            $query->whereNotIn('id', $excludedIds);
        }

        if ($shipId) {
            $shipId = (int) $shipId;
            $regexp = '"ship_id"[[:space:]]*:[[:space:]]*"?'
                . preg_quote((string) $shipId, '/')
                . '"?';
            $query->whereRaw('data REGEXP ?', [$regexp]);
        }

        return $query->take((int) $limit)->get();
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
