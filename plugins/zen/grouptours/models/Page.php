<?php namespace Zen\GroupTours\Models;

use Carbon\Carbon;
use Model;
use October\Rain\Database\Traits\Sortable;
use Zen\GroupTours\Classes\Core;

/**
 * Model
 * @method static active()
 */
class Page extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use Sortable;
    public $timestamps = false;
    public $table = 'zen_grouptours_pages';
    public $rules = [
        'slug' => 'required|unique:zen_grouptours_pages',
    ];

    public $belongsTo = [
        'gallery' => [
            PageGallery::class,
            'key' => 'gallery_block_id'
        ],
        'features' => [
            Feature::class,
            'key' => 'features_block_id'
        ],
        'reviews' => [
            Review::class,
            'key' => 'reviews_block_id'
        ],
    ];

    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    public function getTitleAttribute()
    {
        return $this->label ?? $this->name;
    }

    # Опции выбора блока с галереей
    public function getGalleryBlockIdOptions()
    {
        return [0 => ' -- '] + PageGallery::lists('name', 'id');
    }

    # Данные для блока галереи
    public function tinyboxGallery()
    {
        $gallery = $this->gallery;
        if (!$gallery) {
            return [];
        }

        $images = $gallery->images ?? null;
        if (!$images) {
            return [];
        }

        $images = $images->map(function ($image) {
            return  [
                'src' => Core::resize($image, ['width' => 1200]),
                'caption' => $image->title
            ];
        })->toArray();

        return [
            'images' => $images,
            'title' => $gallery->title
        ];
    }

    # Опции выбора блока с фишками
    public function getFeaturesBlockIdOptions()
    {
        return [0 => ' -- '] + Feature::lists('name', 'id');
    }

    # Данные для блока с фишками
    public function getFeatures()
    {
        $features = $this->features;

        $images = $features->images ?? null;
        if (!$images) {
            return null;
        }

        $images = $images->map(function ($feature) {
            return [
                'image' => Core::resize($feature, ['width' => 500]),
                'title' => $feature->title,
                'desc' => $feature->description
            ];
        })->toArray();

        return [
            'title' => $features->title,
            'sub_title' => $features->sub_title,
            'images' => $images
        ];
    }

    # Опции выбора блока с отзывами
    public function getReviewsBlockIdOptions()
    {
        return [0 => ' -- '] + Review::lists('name', 'id');
    }

    # Данные для блока с отзывами
    public function getReviews($json = false)
    {
        $reviews = $this->reviews;
        if (!$reviews || !$reviews->data) {
            return null;
        }

        $data = collect($reviews->data)->map(function ($item) {
            $item['avatar'] = Core::resize(base_path('storage/app/media' . $item['avatar']));
            $item['image'] = Core::resize(base_path('storage/app/media' . $item['image']));
            $date_time = Carbon::parse($item['date_time']);
            $item['date'] = $date_time->format('d.m.Y');
            $item['time'] = $date_time->format('H:i');
            unset($item['date_time']);
            return $item;
            return $item;
        })->toArray();

        if ($json) {
            return json_encode($data, 256);
        }

        return $reviews->data;
    }
}
