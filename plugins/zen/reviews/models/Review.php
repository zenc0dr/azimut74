<?php namespace Zen\Reviews\Models;

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
}
