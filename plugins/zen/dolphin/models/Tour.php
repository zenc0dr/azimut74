<?php namespace Zen\Dolphin\Models;


use Model;
use Zen\Dolphin\Models\Country;
use DB;
use Zen\Grinder\Classes\Grinder;
use Zen\Dolphin\Classes\Core;
use BackendAuth;

/**
 * Model
 */
class Tour extends Model
{
    use \October\Rain\Database\Traits\Validation;


    /**
     * @var string The database table used by the model.
     */
    public $table = 'zen_dolphin_tours';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    public $attachOne = [
        'preview_image' => [
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

    public $hasMany = [
        'tarrifs' => [
            'Zen\Dolphin\Models\Tarrif'
        ],
    ];

    public $belongsTo = [
        'operator' => [
            'Zen\Dolphin\Models\Operator',
            'key' => 'operator_id'
        ],
        'faq' => [
            Faq::class,
            'key' => 'faq_id'
        ],
    ];

    public $belongsToMany = [
        'labels' => [
            'Zen\Dolphin\Models\Label',
            'table' => 'zen_dolphin_labels_tours',
            'key' => 'tour_id',
            'otherKey' => 'label_id',
        ]
    ];

    public function getMediaImagesAttribute($images_json)
    {
        return json_decode($images_json, true);
    }

    public function setMediaImagesAttribute($images_array)
    {
        $this->attributes['media_images'] = json_encode($images_array, 256);
    }

    public function getOperatorNameAttribute()
    {
        if (!BackendAuth::check()) {
            return;
        }
        return @$this->operator->name;
    }

    public function getTourDatesAttribute()
    {
        $core = new Core;
        $dates = DB::table('zen_dolphin_prices')
            ->where('tour_id', $this->id)
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('date')->map(function ($item) use ($core) {
                return $core->dateFromMysql($item);
            })->toArray();
        return $dates;
    }

    public function getTarrifsCountAttribute()
    {
        $tattifs = $this->tarrifs;
        if ($tattifs) {
            return $this->tarrifs->count();
        }
    }

    public function getLabelsListOptions()
    {
        return Label::lists('name', 'id');
    }

    public function getLabelsListAttribute()
    {
        return DB::table('zen_dolphin_labels_tours')->where('tour_id', $this->id)->lists('label_id');
    }


    private $labelsDump;

    public function setLabelsListAttribute($value)
    {
        $this->labelsDump = $value;
    }

    public function getTypeIdOptions()
    {
        return [
            0 => 'Экскурсионные туры',
            1 => 'Автобусные туры'
        ];
    }

    public function getTypeIdAttribute($type_id)
    {
        return $type_id ?? 0;
    }

    public function getFaqIdOptions()
    {
        return [0 => ' -- '] + Faq::lists('name', 'id');
    }

    public function getTourProgramAttribute($data)
    {
        return json_decode($data, true);
    }

    public function setTourProgramAttribute($data)
    {
        $this->attributes['tour_program'] = json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function getOperatorIdOptions()
    {
        return [0 => '--'] + Operator::lists('name', 'id');
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

        $conditions_ids = collect($conditions)->map(function ($item) {
            return intval($item['condition_id']);
        })->toArray();

        $conditions_titles = Condition::whereIn('id', $conditions_ids)->pluck('name', 'id')->toArray();

        foreach ($conditions as &$condition) {
            $condition['name'] = $conditions_titles[$condition['condition_id']];
        }

        return $conditions;
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
                'src' => Grinder::open($image)->options(['width' => 1200])->getThumb(),
                'title' => $image->title
            ];
        }

        $output_media_images = [];
        foreach ($media_images as $image) {
            $image_path = storage_path('app/media' . $image['src']);
            $output_media_images[] = [
                'src' => Grinder::open($image_path)->options(['width' => 1200])->getThumb(),
                'title' => $image['title'] ?? null
            ];
        }

        return array_merge($output_images, $output_media_images);
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

        $objects = [];
        foreach ($waybill as $item) {
            $geo = explode(':', $item['geo']);

            $model = null;
            $id = null;

            if (strpos($geo[1], 'ps') !== false) {
                $model = 'Place';
                $id = intval(preg_replace('/\D/', '', $geo[1]));
            } else {
                $model = $geoModel[$geo[0]];
                $id = $geo[1];
            }


            $objects[] = app('Zen\Dolphin\Models\\' . $model)->find($id);
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

    public function saveLabels()
    {
        # Защита от сохранения вне админки
        if (post('_token') === null) {
            return;
        }

        DB::table('zen_dolphin_labels_tours')->where('tour_id', $this->id)->delete();

        if (!$this->labelsDump) {
            return;
        }

        $insert = [];
        foreach ($this->labelsDump as $label_id) {
            $insert[] = [
                'label_id' => $label_id,
                'tour_id' => $this->id
            ];
        }

        DB::table('zen_dolphin_labels_tours')->insert($insert);
    }

    public function getDurationTextAttribute()
    {
        $core = new Core;
        return $this->duration . ' ' . $core->incline(['день', 'дня', 'дней'], $this->duration);
    }

    ## EVENTS ########################

    public function afterSave()
    {
        $this->saveLabels();
    }

}
