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

    /** Порядок и подписи оценок для публичного виджета (без reviews.azimut). Порядок как в макете заказчика. */
    private const RATING_DEFINITIONS = [
        'cabin' => 'Каюта',
        'food' => 'Питание',
        'tours' => 'Экскурсии',
        'anim_on_board' => 'Анимация на борту',
        'service' => 'Обслуживание',
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
            'ship_id' => self::normalizeShipId($form['ship_id'] ?? null) ?: null,
            'ship_name' => (string) ($form['ship_name'] ?? ''),
            'text' => $text,
            'date' => $review->created_at ? Carbon::parse($review->created_at)->format('d.m.Y') : '',
        ];

        if ($review->created_at) {
            $out['date_ru'] = self::formatRussianDate(Carbon::parse($review->created_at));
        } else {
            $out['date_ru'] = '';
        }

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

    /**
     * Список теплоходов для фильтра виджета: короткое имя (аксессор standard_name), по алфавиту.
     *
     * @return array<int, array{id:int,label:string}>
     */
    public static function getShipOptions()
    {
        $rows = Motorships::query()->get(['id', 'name']);

        $out = $rows->map(function (Motorships $m) {
            $label = trim((string) $m->standard_name);
            if ($label === '') {
                $label = trim((string) $m->name);
            }

            return [
                'id' => (int) $m->id,
                'label' => $label,
            ];
        });

        return $out
            ->sortBy(function ($row) {
                return mb_strtolower($row['label'], 'UTF-8');
            })
            ->values()
            ->all();
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
            self::applyShipIdFilterToQuery($query, $shipId);
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

    /**
     * Последние по дате отзывы с указанным ship_id в JSON (для SEO и fallback без bindings).
     *
     * @return \Illuminate\Support\Collection<int, Review>
     */
    public static function getLatestReviewsForShip(int $shipId, int $limit = 3)
    {
        $shipId = (int) $shipId;
        $limit = (int) $limit;
        if ($shipId < 1 || $limit < 1) {
            return collect();
        }

        return Review::query()
            ->tap(function ($query) use ($shipId) {
                self::applyShipIdFilterToQuery($query, $shipId);
            })
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Свежие отзывы судна, пропуская review_id, уже привязанные к cruise/transit (и др. не-motorship).
     *
     * @param array<int, bool> $blockedReviewIds review_id => true
     * @return \Illuminate\Support\Collection<int, Review>
     */
    public static function getLatestReviewsForShipUnblocked(int $shipId, int $limit = 3, array $blockedReviewIds = [])
    {
        $shipId = (int) $shipId;
        $limit = (int) $limit;
        if ($shipId < 1 || $limit < 1) {
            return collect();
        }

        $scanLimit = max($limit * 20, 50);
        $candidates = self::getLatestReviewsForShip($shipId, $scanLimit);
        $out = collect();

        foreach ($candidates as $review) {
            $reviewId = (int) $review->id;
            if (isset($blockedReviewIds[$reviewId])) {
                continue;
            }
            $out->push($review);
            if ($out->count() >= $limit) {
                break;
            }
        }

        return $out;
    }

    /** @deprecated Используйте getLatestReviewsForShip */
    public static function getRandomReviewsForShip(int $shipId, int $limit = 3)
    {
        return self::getLatestReviewsForShip($shipId, $limit);
    }

    public static function extractForm(Review $review)
    {
        $data = $review->data ?: [];

        if (isset($data['form']) && is_array($data['form'])) {
            $form = $data['form'];
        } else {
            $form = is_array($data) ? $data : [];
        }

        if (array_key_exists('ship_id', $form)) {
            $normalized = self::normalizeShipId($form['ship_id']);
            $form['ship_id'] = $normalized > 0 ? $normalized : $form['ship_id'];
        }

        return $form;
    }

    /**
     * Приводит ship_id из JSON к int: в data встречается и "99", и 135 без кавычек.
     *
     * @param mixed $value
     */
    public static function normalizeShipId($value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        if (is_bool($value)) {
            return 0;
        }
        if (is_int($value)) {
            return $value > 0 ? $value : 0;
        }
        if (is_float($value)) {
            return $value > 0 ? (int) $value : 0;
        }
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '' || !is_numeric($value)) {
                return 0;
            }

            return (int) $value > 0 ? (int) $value : 0;
        }
        if (is_numeric($value)) {
            return (int) $value > 0 ? (int) $value : 0;
        }

        return 0;
    }

    /**
     * REGEXP для ship_id в сыром JSON: "ship_id": 135 и "ship_id": "135".
     */
    private static function shipIdRegexpPattern(int $shipId): string
    {
        $id = preg_quote((string) $shipId, '/');

        return '"ship_id"[[:space:]]*:[[:space:]]*"?'
            . $id
            . '"?(?![0-9])';
    }

    /**
     * Фильтр по ship_id в колонке data (корень и $.form.ship_id + REGEXP для старых записей).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $shipId
     */
    private static function applyShipIdFilterToQuery($query, $shipId): void
    {
        $shipId = self::normalizeShipId($shipId);
        if ($shipId < 1) {
            return;
        }

        $regexp = self::shipIdRegexpPattern($shipId);
        $query->where(function ($q) use ($shipId, $regexp) {
            $q->whereRaw(
                'CAST(COALESCE(
                    JSON_UNQUOTE(JSON_EXTRACT(data, "$.ship_id")),
                    JSON_UNQUOTE(JSON_EXTRACT(data, "$.form.ship_id"))
                ) AS UNSIGNED) = ?',
                [$shipId]
            )->orWhereRaw('data REGEXP ?', [$regexp]);
        });
    }

    private static function formatRussianDate(Carbon $d): string
    {
        static $months = [
            1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля',
            5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа',
            9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря',
        ];
        $m = (int) $d->format('n');

        return (int) $d->format('j') . ' ' . ($months[$m] ?? $d->format('m')) . ' ' . $d->format('Y');
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
