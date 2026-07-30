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

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public $attachMany = [
        'photos' => \System\Models\File::class
    ];

    public $hasOne = [
        'binding' => [Binding::class, 'key' => 'review_id'],
    ];

    public $hasMany = [
        'reviewPhotos' => [ReviewPhoto::class, 'key' => 'review_id'],
    ];

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeWithPhotoMeta($query)
    {
        return $query->with(['reviewPhotos', 'photos']);
    }

    public function getPhotoMeta(int $fileId): ?ReviewPhoto
    {
        if ($this->relationLoaded('reviewPhotos')) {
            return $this->reviewPhotos->firstWhere('system_file_id', $fileId);
        }

        return $this->reviewPhotos()
            ->where('system_file_id', $fileId)
            ->first();
    }

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
     * Автоснятие с публикации: оценка отдыха или Азимут ≤ 3.
     *
     * @param array<string, mixed> $form
     */
    public static function shouldAutoUnpublish(array $form): bool
    {
        $reviews = $form['reviews'] ?? null;
        if (!is_array($reviews)) {
            return false;
        }

        foreach (['cruise', 'azimut'] as $key) {
            if (!array_key_exists($key, $reviews)) {
                continue;
            }
            $value = $reviews[$key];
            if ($value === null || $value === '') {
                continue;
            }
            if (!is_numeric($value)) {
                continue;
            }
            if ((int) round((float) $value) <= 3) {
                return true;
            }
        }

        return false;
    }

    public function getReviewsTextAttribute(): string
    {
        $form = $this->reviewFormData();

        return (string) ($form['reviews_text'] ?? '');
    }

    public function setReviewsTextAttribute($value): void
    {
        $this->mutateFormField('reviews_text', (string) $value);
    }

    public function getAdminRatingCruiseAttribute(): ?int
    {
        return $this->ratingFromForm('cruise');
    }

    public function setAdminRatingCruiseAttribute($value): void
    {
        $this->mutateReviewsField('cruise', $value);
    }

    public function getAdminRatingAzimutAttribute(): ?int
    {
        return $this->ratingFromForm('azimut');
    }

    public function setAdminRatingAzimutAttribute($value): void
    {
        $this->mutateReviewsField('azimut', $value);
    }

    /**
     * Данные формы из JSON (для списка в бэкенде).
     *
     * @return array<string, mixed>
     */
    protected function reviewFormData(): array
    {
        $d = $this->data;

        return is_array($d) ? ReviewsWidget::extractForm($this) : [];
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

    private function ratingFromForm(string $key): ?int
    {
        $data = $this->reviewFormData();
        $reviews = $data['reviews'] ?? null;
        if (!is_array($reviews) || !array_key_exists($key, $reviews)) {
            return null;
        }
        $v = $reviews[$key];
        if ($v === null || $v === '') {
            return null;
        }
        if (!is_numeric($v)) {
            return null;
        }

        return (int) round((float) $v);
    }

    private function mutateFormField(string $field, $value): void
    {
        $this->mutateFormData(function (array &$form) use ($field, $value) {
            $form[$field] = $value;
        });
    }

    private function mutateReviewsField(string $key, $value): void
    {
        $this->mutateFormData(function (array &$form) use ($key, $value) {
            if (!isset($form['reviews']) || !is_array($form['reviews'])) {
                $form['reviews'] = [];
            }
            if ($value === null || $value === '') {
                $form['reviews'][$key] = null;

                return;
            }
            $form['reviews'][$key] = (int) $value;
        });
    }

    /**
     * @param callable(array<string, mixed>&): void $mutator
     */
    private function mutateFormData(callable $mutator): void
    {
        $data = $this->data;
        if (!is_array($data)) {
            $data = [];
        }

        $wrapped = isset($data['form']) && is_array($data['form']);
        $form = $wrapped ? $data['form'] : $data;
        $mutator($form);

        if ($wrapped) {
            $data['form'] = $form;
        } else {
            $data = $form;
        }

        $this->data = $data;
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
