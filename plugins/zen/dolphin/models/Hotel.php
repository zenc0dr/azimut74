<?php namespace Zen\Dolphin\Models;

use Model;
use DB;
use October\Rain\Exception\ApplicationException;
use Zen\Dolphin\Classes\Core;
use Zen\Cabox\Classes\Cabox;
use Log;
use Zen\Dolphin\Traits\MultiGenerator;
use Zen\Grinder\Classes\Grinder;

/**
 * Model
 */
class Hotel extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\Sortable;
    use MultiGenerator;

    public $table = 'zen_dolphin_hotels';

    protected $fillable = ['snippets'];

    public $rules = [
    ];

    public $belongsTo = [
        'country' => [
            'Zen\Dolphin\Models\Country',
            'order' => 'sort_order'
        ],
        'region' => [
            'Zen\Dolphin\Models\Region',
            'order' => 'sort_order'
        ],
        'city' => [
            'Zen\Dolphin\Models\City',
            'order' => 'sort_order'
        ],
        'type' => [
            'Zen\Dolphin\Models\HotelType',
            'order' => 'sort_order'
        ],
        'operator' => [
            'Zen\Dolphin\Models\Operator'
        ],
    ];

    public $attachOne = [
        'preview_image' => 'System\Models\File'
    ];

    public $attachMany = [
        'images' => [
            'System\Models\File',
            'order' => 'sort_order',
            'delete' => true
        ]
    ];

    public $belongsToMany = [
        'structures' => [
            'Zen\Dolphin\Models\Hstructure',
            'table'    => 'zen_dolphin_hstructures_pivot',
            'key'      => 'hotel_id',
            'otherKey' => 'structure_id',
        ],
        'services' => [
            'Zen\Dolphin\Models\Hservice',
            'table'    => 'zen_dolphin_hservices_pivot',
            'key'      => 'hotel_id',
            'otherKey' => 'service_id',
        ],
        'options' => [
            'Zen\Dolphin\Models\Hopt',
            'table'    => 'zen_dolphin_hopts_pivot',
            'key'      => 'hotel_id',
            'otherKey' => 'hopt_id',
        ],
        'plages' => [
            'Zen\Dolphin\Models\Plage',
            'table'    => 'zen_dolphin_plages_pivot',
            'key'      => 'hotel_id',
            'otherKey' => 'plage_id',
        ],

    ];

    ###################################################################################

    function getTypeIdOptions()
    {
        return [0 => '--'] + HotelType::lists('name', 'id');
    }

    function getOperatorIdOptions()
    {
        return [0 => '--'] + Operator::lists('name', 'id');
    }

    function getCountryIdOptions()
    {
        return [0 => '--'] + Country::lists('name', 'id');
    }

    function getRegionIdOptions()
    {
        return [0 => '--'] + Region::lists('name', 'id');
    }

    function setGalleryAttribute($images)
    {
        if(!$images) return;
        if(!is_array($images)) throw new ApplicationException('images должны быть массивом ссылок');
        $this->attributes['gallery'] = json_encode($images, JSON_UNESCAPED_UNICODE);
    }

    function setGpsAttribute($gps)
    {
        # Защита от сохранения вне админки
        if(post('_token') === null) return;

        if(!$gps) {
            $this->attributes['gps'] = null;
            return;
        };

        $gps = str_replace(' ', '', $gps);
        $gps = str_replace(',', ':', $gps);
        $gps = preg_replace('/[а-яА-Яa-zA-Z]/', '', $gps);

        if(!preg_match('/\d{2}\.\d+:\d{2}\.\d+/', $gps))
            throw new ApplicationException('Не правильный формат GPS (пример: 44.243199:38.834012)');
        $this->attributes['gps'] = $gps;
    }

    function getGeoDataAttribute()
    {
        if($this->exists) {
            $hotel = [
                'country_id' => $this->country_id,
                'region_id' => $this->region_id,
                'city_id' => $this->city_id
            ];

        } else {
            $hotel = [
                'country_id' => 1,
                'region_id' => 0,
                'city_id' => 0
            ];
        }

        $empty = [[
            'id' => 0,
            'name' => '--'
        ]];

        $arr = [
            'hotel' => $hotel,
            'country' => Country::select('id', 'name')->get()->toArray(),
            'region' => array_merge($empty, Region::select('id', 'name', 'country_id')->get()->toArray()),
            'city' => array_merge($empty, City::select('id', 'name', 'country_id', 'region_id')->get()->toArray()),
        ];

        return json_encode($arr, JSON_UNESCAPED_UNICODE);
    }

    # Все картинки по упрощённой модели
    function getPicturesAttribute()
    {
        $output = [];
        $preview = ($this->preview_image) ? Grinder::open($this->preview_image)->options('w1200')->getThumb() : null;

        if($preview) $output[] = $preview;

        $images = $this->images;
        if($images->count()) {
            foreach ($images as $image) {
                $output[] = Grinder::open($image)->options('w1200')->getThumb();
            }
        }

        $gallery = $this->gallery;
        if($gallery) {
            $output = array_merge($output, $gallery);
        }

        return $output;
    }

    function getOnePicAttribute()
    {

        if($this->preview_image) {

            return Grinder::open($this->preview_image)->options('w1200')->getThumb();
        }

        if($this->images->count()) {
            return Grinder::open($this->images[0])->options('w1200')->getThumb();
        }

        if($this->gallery) {
            return $this->gallery[0];
        }

        return '/plugins/zen/dolphin/assets/images/tour-no-image.jpg';
    }

    # Галерея внешних изображений
    function getGalleryAttribute($images)
    {
        return json_decode($images, 1);
    }

    # Число звёзд по еб###той системе Dolphin API
    function getDolphinStarsAttribute()
    {
        $stars = $this->stars;
        $stars = ($stars)?:0;
        $realStars = 1;
        if($stars == 1) $realStars = 3;
        if($stars == 2) $realStars = 2;
        if($stars == 3) $realStars = 4;
        if($stars == 4) $realStars = 5;
        return $realStars;
    }

    # Вернуть звёздочки (графические)
    function getDrawStars()
    {
        $stars = $this->stars;

        # Поправка на исчточник Дельфин
        if($this->created_by == 'dolphin') {
            $stars = $this->dolphin_stars;
        }

        $return = '';
        while ($stars > 0) {
            $return .= '★';
            $stars--;
        }

        return $return;
    }

    # Вернуть гео-объект
    function getGeoObjectAttribute()
    {
        if($this->city) return $this->city;
        if($this->region) return $this->region;
        if($this->country) return $this->country;
    }

    function getGeoCodeAttribute()
    {
        if($this->city) return '2:'.$this->city->id;
        if($this->region) return '1:'.$this->region->id;
        if($this->country) return '0:'.$this->country->id;
    }

    # Вернуть имя гео-объекта
    function getGeoNameAttribute()
    {
        if($this->city) return $this->city->name;
        if($this->region) return $this->region->name;
        if($this->country) return $this->country->name;
    }

    # Сохранить гео-объект из JSON
    function setSaveGeoAttribute($jsonValue)
    {
        $value = (object) json_decode($jsonValue, 1);
        $this->attributes['country_id'] = $value->country_id;
        $this->attributes['region_id'] = $value->region_id;
        $this->attributes['city_id'] = $value->city_id;
    }

    # Вернуть дамп дельфина
    function dolphinDump($option=null)
    {

        if($this->created_by != 'dolphin') return;

        $cache = new Cabox('dolphin.parsers');
        $core = new Core;

        $dump = $cache->get('dolpin.hotel.id#'.$this->eid);


        if(!$dump) {
            $dump = $core->store('Dolphin')->query('HotelContent?id='.$this->eid, ['cache_key' => 'dolpin.hotel.id#'.$this->eid]);
        };

        if(!$dump) return;

        if($option == 'html') {
            return $core->htmlDump($dump);
        }

        return $dump;
    }

    function getThumbsAttribute()
    {
        return $this->geo_object->thumbs;
    }


    ################## Additional #####################
    # Набор данных хранящихся в json массиве additional
    # потому что не надо засорять базу данных

    private $additionalData;

    function getAdditionalAttribute($json)
    {
        return json_decode($json, true);
    }

    function setAdditionalAttribute($array)
    {
        $this->attributes['additional'] = json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function saveAdditional()
    {
        if(!$this->additionalData) return;
        if($this->additional) {
            $this->additional = array_merge($this->additional, $this->additionalData);
        } else {
            $this->additional = $this->additionalData;
        }
    }

    # Примечание
    function getNoteAttribute()
    {
        return @$this->additional['note'];
    }
    function setNoteAttribute($value)
    {
        $this->additionalData['note'] = $value;
    }


    # Платные услуги
    function getPaidServicesAttribute()
    {
        return @$this->additional['paid_services'];
    }
    function setPaidServicesAttribute($value)
    {
        $this->additionalData['paid_services'] = $value;
    }

    # Проезд
    function getHowToGetAttribute()
    {
        return @$this->additional['how_to_get'];
    }
    function setHowToGetAttribute($value)
    {
        $this->additionalData['how_to_get'] = $value;
    }

    # Трансфер
    function getTransferAttribute()
    {
        return @$this->additional['transfer'];
    }
    function setTransferAttribute($value)
    {
        $this->additionalData['transfer'] = $value;
    }

    # Показания для лечения
    function getTherapyAttribute()
    {
        return @$this->additional['therapy'];
    }
    function setTherapyAttribute($value)
    {
        $this->additionalData['therapy'] = $value;
    }

    # Профиль лечения
    function getTreatmentAttribute()
    {
        return @$this->additional['treatment'];
    }
    function setTreatmentAttribute($value)
    {
        $this->additionalData['treatment'] = $value;
    }

    # Описание номеров numbers_desc
    function getNumbersDescAttribute()
    {
        return @$this->additional['numbers_desc'];
    }
    function setNumbersDescAttribute($value)
    {
        $this->additionalData['numbers_desc'] = $value;
    }

    # Массив данных для вывода
    function getDataAttribute()
    {
        $core = new Core;
        return [
            'name' => $this->name,
            'type' => @$this->type->name,
            'stars' => $this->getDrawStars(),
            'address' => $this->address,
            'preview_image' => $this->one_pic,
            'pictures' => $this->pictures,
            'gallery' => $core->store('Gallery')->get($this->pictures),
            'info' => $this->info, // Описание гостиницы
            'numbers_desc' => $this->numbers_desc, // Описание номеров
            'ci' => $this->ci, // Информация о строительстве
            'ta' => $this->ta,  // Транспортная доступность
            'contacts' => $this->contacts, // Контакты
            'paid_services' => $this->paid_services, // Платные услуги
            'how_to_get' => $this->how_to_get, // Проезд
            'transfer' => $this->transfer, // Трансфер
            'therapy' => $this->therapy, // Показания для лечения
            'treatment' => $this->treatment, // Профиль лечения
            'hstructures' => $this->structures->pluck('name')->toArray(), // Инфраструктура гостиницы
            'hservices' => $this->services->pluck('name')->toArray(), // Услуги гостиницы
            'hopts' => $this->options->pluck('name')->toArray(), // Характеристики гостиницы
            'plages' => $this->plages->pluck('name')->toArray(), // Пляжи
            'to_sea' => $this->to_sea,
            'note' => $this->note
        ];
    }

    function getAtmLinkAttribute()
    {
        $core = new Core;
        $page = $core->model('Page')->find($core->settings('default_page_atm'));
        $url = "/ex-tours/atm/{$page->slug}/h{$this->id}";
        return $url;
    }

    #### Списки мультивыбора ####
    # Инфраструктура гостиницы = Hstructure === HstructureMulti = hstructure_multi
    # Услуги гостиницы = Hservice ============= HserviceMulti === hservice_multi
    # Характеристики гостиницы = Hopt ========= HoptsMulti ====== hopts_multi
    # Пляж = Plage ============================ PlageMulti ====== plage_multi

    public
        $this_key = 'hotel_id',
        $HstructureDump,
        $HserviceDump,
        $HoptDump,
        $PlageDump;

    function getHstructureMultiOptions()
    {
        return $this->optionsMultiGenerator(Hstructure::class);
    }

    function getHstructureMultiAttribute()
    {
        return $this->getMultiGenerator('zen_dolphin_hstructures_pivot', 'structure_id');
    }

    function setHstructureMultiAttribute($value)
    {
        $this->setMultiGenerator('Hstructure', $value);
    }


    function getHserviceMultiOptions()
    {
        return $this->optionsMultiGenerator(Hservice::class);
    }

    function getHserviceMultiAttribute()
    {
        return $this->getMultiGenerator('zen_dolphin_hservices_pivot', 'service_id');
    }

    function setHserviceMultiAttribute($value)
    {
        $this->setMultiGenerator('Hservice', $value);
    }


    function getHoptsMultiOptions()
    {
        return $this->optionsMultiGenerator(Hopt::class);
    }

    function getHoptsMultiAttribute()
    {
        return $this->getMultiGenerator('zen_dolphin_hopts_pivot', 'hopt_id');
    }

    function setHoptsMultiAttribute($value)
    {
        $this->setMultiGenerator('Hopt', $value);
    }

    function getPlageMultiOptions()
    {
        return $this->optionsMultiGenerator(Plage::class);
    }

    function getPlageMultiAttribute()
    {
        return $this->getMultiGenerator('zen_dolphin_plages_pivot', 'plage_id');
    }

    function setPlageMultiAttribute($value)
    {
        $this->setMultiGenerator('Plage', $value);
    }

    function setTypeIdAttribute($value)
    {
        $this->attributes['type_id'] = $value ?? 0;
    }

    ######### Events

    function beforeSave()
    {
        $this->saveAdditional();
    }

    function afterSave()
    {
        $generator_options = [
            [
                'model' => 'Hstructure',
                'pivot' => 'zen_dolphin_hstructures_pivot',
                'key' => 'structure_id'
            ],
            [
                'model' => 'Hservice',
                'pivot' => 'zen_dolphin_hservices_pivot',
                'key' => 'service_id'
            ],
            [
                'model' => 'Hopt',
                'pivot' => 'zen_dolphin_hopts_pivot',
                'key' => 'hopt_id'
            ],
            [
                'model' => 'Plage',
                'pivot' => 'zen_dolphin_plages_pivot',
                'key' => 'plage_id'
            ],
        ];

        $this->saveMultiGenerator($generator_options);
    }

    function afterDelete()
    {
        DB::table('zen_dolphin_plages_pivot')->where('hotel_id')->delete();
        DB::table('zen_dolphin_hopts_pivot')->where('hotel_id')->delete();
        DB::table('zen_dolphin_hservices_pivot')->where('hotel_id')->delete();
        DB::table('zen_dolphin_hstructures_pivot')->where('hotel_id')->delete();
    }

}
