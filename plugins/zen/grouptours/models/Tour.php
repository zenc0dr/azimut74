<?php namespace Zen\GroupTours\Models;

use Model;
use Zen\Dolphin\Models\Condition;
use Zen\Dolphin\Models\Country;
use Zen\Dolphin\Models\Place;
use Zen\GroupTours\Classes\Core;

/**
 * Model
 * @method static active()
 */
class Tour extends Model
{
    use \October\Rain\Database\Traits\Validation;

    public $timestamps = false;
    public $table = 'zen_grouptours_tours';

    public $attachOne = [
        'preview' => [
            'System\Models\File',
            'delete' => true
        ],
    ];

    public $attachMany = [
        'images' => [
            'System\Models\File',
            'order' => 'sort_order',
            'delete' => true
        ],
    ];

    public $belongsToMany = [
        'tags' => [
            'Zen\GroupTours\Models\Tag',
            'table'    => 'zen_grouptours_tours_tags_pivot',
            'key'      => 'tour_id',
            'otherKey' => 'tag_id',
        ]
    ];

    public $rules = [
        'name' => 'required|unique:zen_grouptours_tours',
        'days' => 'required',
        'price' => 'required'
    ];

    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    public function getPreviewImageAttribute()
    {
        if (!$this->preview) {
            return '/plugins/zen/grouptours/assets/images/tour-no-image.jpg';
        }

        return Core::resize($this->preview);
    }

    public function getWaybillAttribute($data)
    {
        if (!$data) {
            return null;
        }

        $output = [];
        $data = explode(' ', $data);
        foreach ($data as $item) {
            $output[] = [
                'geo' => $item
            ];
        }

        return $output;
    }

    public function setWaybillAttribute($data)
    {
        $line = [];
        foreach ($data as $item) {
            $line[] = $item['geo'];
        }
        $line = join(' ', $line);
        $this->attributes['waybill'] = $line;
    }

    public function getWaybillObjectsAttribute()
    {
        $geoModel = [
            0 => 'Country',
            1 => 'Region',
            2 => 'City',
        ];

        $waybill = $this->waybill;

        if (!$waybill) {
            return [];
        }

        $objects = [];
        foreach ($waybill as $item) {
            $geo = explode(':', $item['geo']);
            if (strpos($geo[1], 'ps') !== false) {
                $model = 'Place';
                $id = intval(preg_replace('/\D/', '', $geo[1]));
            } else {
                $model = $geoModel[$geo[0]];
                $id = $geo[1];
            }
            $geo_object = app('Zen\Dolphin\Models\\' . $model)->find($id);
            if ($geo_object) {
                $objects[] = $geo_object;
            }
        }

        return $objects;
    }

    public function getWaybillArrayAttribute()
    {
        $objects = $this->waybill_objects;
        $array = [];
        foreach ($objects as $object) {
            $array[] = $object->name;
        }
        return $array;
    }

    public function getGeoOptions()
    {
        $countries = Country::get();
        $geo_list = [];
        foreach ($countries as $country) {
            $geo_list['0:' . $country->id] = 'Стр: ' . $country->name;
            if ($country->regions) {
                foreach ($country->regions as $region) {
                    $geo_list['1:' . $region->id] = '- Рег: ' . $region->name;
                    if ($region->cities) {
                        foreach ($region->cities as $city) {
                            $geo_list['2:' . $city->id] = '-- Гор: ' . $city->name;
                        }
                    }
                }
            }
        }

        $places_table = Place::getPlacesTable();

        $output = [];
        foreach ($geo_list as $id => $record) {
            $output[$id] = $record;
            if (isset($places_table[$id])) {
                foreach ($places_table[$id] as $place) {
                    $place_id = $place['id'];
                    $place_name = $place['name'];
                    $output[$place_id] = 'Место: ' . $place_name;
                }
            }
        }

        return $output;
    }

    public function getSnippetAttribute()
    {
        $preview = $this->preview;
        if (!$preview) {
            return '/plugins/zen/grouptours/assets/images/tour-no-image.jpg';
        }
        return Core::resize($this->preview, ['width' => 500]);
    }

    public function getTagsArrayAttribute()
    {
        $tags = $this->tags;
        if (!$tags) {
            return [];
        }
        $tags = $tags->map(function ($tag) {
            return [
                'id' => $tag->id,
                'name' => $tag->name,
            ];
        })->toArray();
        return $tags;
    }

    public function getMediaImagesAttribute($images_json)
    {
        return json_decode($images_json, true);
    }

    public function setMediaImagesAttribute($images_array)
    {
        $this->attributes['media_images'] = json_encode($images_array, 256);
    }

    public function getTourProgramAttribute($data)
    {
        return json_decode($data, true);
    }

    public function setTourProgramAttribute($data)
    {
        $this->attributes['tour_program'] = json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function getConditionIdOptions()
    {
        return Condition::lists('name', 'id');
    }

    public function getConditionsAttribute($data)
    {
        return json_decode($data, true);
    }

    public function setConditionsAttribute($data)
    {
        $this->attributes['conditions'] = json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function getConditionsArrayAttribute()
    {
        $conditions = $this->conditions;

        if (!$conditions) {
            return [];
        }

        $conditions_ids = collect($conditions)->map(function ($item) {
            return intval($item['condition_id']);
        })->toArray();

        $conditions_titles = Condition::whereIn('id', $conditions_ids)->pluck('name', 'id')->toArray();

        foreach ($conditions as &$condition) {
            $condition['name'] = $conditions_titles[$condition['condition_id']];
        }

        return $conditions;
    }

    public function getGalleryAttribute()
    {
        $images = $this->images ?? [];
        $media_images = $this->media_images ?? [];

        if (!$images && !$media_images) {
            return null;
        }

        $output_images = [];
        foreach ($images as $image) {
            $output_images[] = [
                'src' => Core::resize($image, ['width' => 1200]),
                'title' => $image->title
            ];
        }

        $output_media_images = [];
        foreach ($media_images as $image) {
            $image_path = storage_path('app/media' . $image['src']);
            $output_media_images[] = [
                'src' => Core::resize($image_path, ['width' => 1200]),
                'title' => $image['title'] ?? null
            ];
        }

        return array_merge($output_images, $output_media_images);
    }
}
