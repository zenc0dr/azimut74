<?php namespace Zen\Reviews\Models;

use Mcmraak\Rivercrs\Classes\ReviewsWidget;
use Mcmraak\Rivercrs\Models\Motorships;
use Model;

/**
 * Model
 */
class Review extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'zen_reviews_reviews';

    /**
     * @var array Validation rules
     */
    public $rules = [];

    public $attachMany = [
        'photos' => \System\Models\File::class
    ];

    public $hasOne = [
        'binding' => [Binding::class, 'key' => 'review_id'],
    ];

    public function setDataAttribute($value): void
    {
        $this->attributes['data'] = reviews()->toJson($value, true, true);
    }

    public function getDataAttribute($value): ?array
    {
        if (!$value) {
            return null;
        }
        return reviews()->fromJson($value);
    }

    /**
     * Данные формы из JSON (для списка в бэкенде).
     *
     * @return array<string, mixed>
     */
    protected function reviewFormData(): array
    {
        $d = $this->data;

        return is_array($d) ? $d : [];
    }

    /**
     * Имя теплохода без слова «Теплоход» и без скобок проекта — как standard_name у судна в RiverCRS.
     */
    public function getShipShortNameAttribute(): string
    {
        $form = ReviewsWidget::extractForm($this);
        $shipId = ReviewsWidget::normalizeShipId($form['ship_id'] ?? null);
        if ($shipId > 0 && class_exists(Motorships::class)) {
            $ship = Motorships::find($shipId);
            if ($ship) {
                return trim((string) $ship->standard_name);
            }
        }

        $raw = isset($form['ship_name']) ? (string) $form['ship_name'] : '';

        return self::normalizeShipNameString($raw);
    }

    /** «Как бы Вы оценили свой отдых в целом?» — form.reviews.cruise */
    public function getRatingVacationAttribute(): string
    {
        return $this->formatRatingFromForm('cruise');
    }

    /** «Как бы Вы оценили работу компании Азимут?» — form.reviews.azimut */
    public function getRatingAzimutAttribute(): string
    {
        return $this->formatRatingFromForm('azimut');
    }

    private function formatRatingFromForm(string $key): string
    {
        $data = $this->reviewFormData();
        $reviews = $data['reviews'] ?? null;
        if (!is_array($reviews) || !array_key_exists($key, $reviews)) {
            return '—';
        }
        $v = $reviews[$key];
        if ($v === null || $v === '') {
            return '—';
        }
        if (!is_numeric($v)) {
            return '—';
        }
        $n = (int) round((float) $v);

        return (string) $n;
    }

    private static function normalizeShipNameString(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }
        $name = preg_replace('/\([^(]+\)/', '', $name);
        $name = str_replace('"', '', $name);
        $name = str_replace('Теплоход', '', $name);

        return trim($name);
    }
}
