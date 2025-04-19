<?php namespace Mcmraak\Rivercrs\Models;
# ".docs\models\1. Теполходы.md"

use Carbon\Carbon;
use Mcmraak\Rivercrs\Classes\Search;
use Model;
use October\Rain\Exception\ApplicationException;
use DB;
use \Backend\Facades\BackendAuth;
use Queue;
use Mcmraak\Rivercrs\Classes\CacheSettings;
use ToughDeveloper\ImageResizer\Classes\Image;
use Zen\Grinder\Classes\Grinder;
use Config;

/**
 * Model
 */
class Motorships extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /*
     * Validation
     */
    public $rules = [
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'mcmraak_rivercrs_motorships';

    public $thisId; // for detect id and model method (create or update)
    public $techsDump; // for saving after create
    public $onboardsDump;
    public $priceDump;

    #MS01#
    public $attachMany = [
        'images' => ['System\Models\File', 'order' => 'sort_order', 'delete' => true],
        'scheme' => ['System\Models\File', 'order' => 'sort_order', 'delete' => true],
    ];

    #MS02#
    public $hasMany = [
        'techsc' => [
            'Mcmraak\Rivercrs\Models\TechsPivot',
            'key' => 'motorship_id'
        ],
        'onboardsc' => [
            'Mcmraak\Rivercrs\Models\OnboardPivot',
            'key' => 'motorship_id'
        ],
        'checkins' => [
            'Mcmraak\Rivercrs\Models\Checkins',
            'key' => 'motorship_id'
        ],
        'cabinslist' => [
            'Mcmraak\Rivercrs\Models\Cabins',
            'key' => 'motorship_id'
        ],
    ];

    public $belongsTo = [
        'status' => [
            ShipStatus::class,
            'key' => 'status_id'
        ]
    ];

    public $belongsToMany = [
        'onboard_list' => [
            'Mcmraak\Rivercrs\Models\Onboard',
            'table'    => 'mcmraak_rivercrs_onboard_pivot',
            'key'      => 'motorship_id',
            'otherKey' => 'onboard_id'
        ]
    ];

    public function getShipSchemeAttribute()
    {
        $schemes = $this->scheme;

        if (!$schemes) {
            return null;
        }

        if (!isset($schemes[0]) || !isset($schemes[0]->path)) {
            return null;
        }

        return $schemes[0]->path;
    }

    function getStatusIdOptions()
    {
        return [0 => ' -- '] + ShipStatus::lists('name', 'id');
    }

    function getAltNameAttribute()
    {
        return ($this->extra_name) ? $this->extra_name : $this->name;
    }

    public function getStandardNameAttribute()
    {
        $name = $this->name;
        $name = preg_replace('/\([^(]+\)/', '', $name);
        $name = str_replace('"', '', $name);
        $name = str_replace('Теплоход', '', $name);
        return trim($name);
    }

    public function getAvatar($width=500, $height=500, $options = ['quality' => 70])
    {
        $images = $this->images;

        if($images->count()) {
            $image = new Image($images[0]->path);
            $image->resize($width, $height, $options);
            return $image->__toString();
        }

        return config('app.url').'themes/azimut-tur/assets/images/nophoto.png';
    }

    # Получение превьюхи быстро (для про-круиз)
    public function getPicAttribute()
    {
        $image = $this->images[0] ?? null;
        if ($image) {
            $image = Grinder::open($image)->getThumb();
        }
        if ($image) {
            return $image;
        }
        return '/themes/prokruiz/assets/img/ship-mini.png';
    }

    public function setExistRoomsAttribute($json_string)
    {
        if(!$json_string) return;
        $json_arr = json_decode($json_string, 1);
        $return = [];
        foreach ($json_arr as $item) {
            $return[] = [
                'n' => (string) $item['n'],
                'c' => intval($item['c']),
                'w' => intval($item['w']),
                'h' => intval($item['h']),
                'x' => intval($item['x']),
                'y' => intval($item['y']),
            ];
        }
        $this->attributes['exist_rooms'] = json_encode ($return, JSON_UNESCAPED_UNICODE);
    }

    public function cabinsJson()
    {
        $cabins = $this->cabinslist;
        $return = [];
        foreach ($cabins as $cabin) {
            $return[$cabin->id] = $cabin->category;
        }
        return json_encode ([0 => ' -- '] + $return, JSON_UNESCAPED_UNICODE);
    }

    function getYoutubeLinkAttribute()
    {
        if(!$this->youtube) return;
        $vcode = preg_replace("/[\r\n\t]/", '', @$this->youtube[0]['vcode']);
        preg_match('/src="([^"]*)"/', $vcode, $m);
        return @$m[1];
    }

    public static function cleanQuotes($string)
    {
        preg_match('/"(.+)"/', $string, $match);
        return isset($match[1]) ? $match[1] : $string;
    }

    # get motorsips names without quotes
    public static function cleanNames($limit = null)
    {
        DB::unprepared("SET sql_mode = ''");
        return DB::table('mcmraak_rivercrs_checkins as checkin')
            ->join('mcmraak_rivercrs_motorships as ship', 'ship.id', '=', 'checkin.motorship_id')
            ->groupBy('ship.id')
            ->orderBy('sort_order')
            ->select('ship.id as id', 'ship.name as name', 'ship.extra_name as extra_name')
            ->take($limit)
            ->get()
            ->map(function ($item)
            {
                if ($item->extra_name) {
                    return [
                        'id' => $item->id,
                        'name' => $item->extra_name
                    ];
                } else {
                    return [
                        'id' => $item->id,
                        'name' => self::cleanQuotes($item->name)
                    ];
                }
            })->toArray();
    }

    function getTemporaryDiscountsAttribute()
    {
        $now_date = date('Y-m-d 00:00:00');
        $now_date_carbon = Carbon::parse($now_date);

        $discounts = DB::table('mcmraak_rivercrs_discounts')->where('valid_until', '>=', $now_date)
            ->where('motorships', 'like', "%#{$this->id}#%")
            ->get();
        $output = [];

        foreach ($discounts as $discount) {
            if(strpos($discount->motorships, "#{$this->id}#") !== false) {
                $until = Carbon::parse($discount->valid_until);
                $before_until = $now_date_carbon->diffInDays($until, false);
                $title = ($before_until <= $discount->overlap_activation) ? $discount->overlap_title : $discount->title;
                $output[] = [
                    'image' => $discount->image,
                    'title' => $title,
                    'text' => $discount->desc,
                ];
            }
        }
        return $output;
    }

    public function cleanSelfName()
    {
        return $this->extra_name ?: self::cleanQuotes($this->name);
    }

    # get motorsip name without quotes
    public static function cleanName($name)
    {
        return self::cleanQuotes($name);
    }

    # Dropdown in techs option
    public function getTcOptions ()
    {
        return Techs::lists('name', 'id');
    }

    # Get list of possible options for "Onboards"
    public function getOnboardsOptions () {
        return Onboard::orderBy('id')->lists('name', 'id');
    }

    # Get checked values to "Onboards" checkboxlist
    public function getOnboardsAttribute ()
    {
        return OnboardPivot::where('motorship_id', $this->id)
                             ->orderBy('onboard_id')
                             ->lists('onboard_id');
    }

    # Motorships "Techs" count on list
    public function getTechscAttribute()
    {
        return $this->techsc()->count();
    }

    # Motorships "Onboards" count on list
    public function getOnboardscAttribute()
    {
        return $this->onboardsc()->count();
    }

    # Motorships "Waybills" count on list
    public function getWaybillscountAttribute()
    {
        return $this->checkins()->count();
    }


    # Motorships get specifications on form
    public function getTechsAttribute()
    {
        $techs = TechsPivot::where('motorship_id', $this->id)->orderBy('tech_id')->get();
        $repeater = [];
        foreach ($techs as $value) {
            $repeater[] = ['tc' => $value->tech_id, 'vl' => $value->value];
        }
        return $repeater;
    }

    function getTechsArrAttribute()
    {
        // mcmraak_rivercrs_techs_pivot
        // mcmraak_rivercrs_techs
        $pivot = DB::table('mcmraak_rivercrs_techs_pivot as pivot')
            ->where('pivot.motorship_id', '=', $this->id)
            ->join('mcmraak_rivercrs_techs as tech', 'tech.id', '=', 'pivot.tech_id')
            ->select(
                'tech.name as name',
                'pivot.value as value'
            )->orderBy('tech.name')->get();
        $output = [];
        foreach ($pivot as $item) {
            $output[] = [
                'name' => $item->name,
                'value' => $item->value
            ];
        }
        return $output;
    }

    function getOnboardsArrAttribute()
    {
        return DB::table('mcmraak_rivercrs_onboard_pivot as pivot')
            ->where('pivot.motorship_id', '=', $this->id)
            ->join('mcmraak_rivercrs_onboard as onboard', 'onboard.id', '=', 'pivot.onboard_id')
            ->select(
                'onboard.name as name'
            )->orderBy('onboard.name')->get()->pluck('name')->toArray();
    }

    public function technicals() {
        return DB::table('mcmraak_rivercrs_techs_pivot')
            ->where('motorship_id',$this->id)
            ->leftJoin(
                'mcmraak_rivercrs_techs',
                'mcmraak_rivercrs_techs.id',
                '=',
                'mcmraak_rivercrs_techs_pivot.tech_id'
            )
            ->select(
                'mcmraak_rivercrs_techs.name as name',
                'mcmraak_rivercrs_techs_pivot.value as value'
            )
            ->get();
    }

    /**
     * Descriptions of cabins cataloged by decks
     * @return array
    */

    public function decksWithCabins()
    {
        /*
       Зная id теплохода мы должны разложить все его кают по палубам

        1) цикл перебирает палубы
        2) цикл перебирает каюты этого теплохода и печатает их если есть совпадение по палубе

       */
        $arr = [];

        $cabins = $this->cabinslist;

        foreach ($cabins as $cabin)
        {
            $decks = $cabin->decks_list;

            foreach ($decks as $deck)
            {
                $arr[$deck->id][] = $cabin;
            }
        }

        $return = [];

        foreach ($arr as $key => $value)
        {
            $desc = Decks::find($key);
            $return[] = [
                'sort_order' => $desc->sort_order,
                'deck' => $desc,
                'cabins' => $value
            ];
        }

        /* reorder by descs */
        $return = array_values(array_sort($return, function ($value) {
            return $value['sort_order'];
        }));

        return $return;
    }

    # Get motorships collection with pricing
    public static function getMotorshipsWitchPrice()
    {
        /* [SQL RAW WITH PRICE]
        SELECT DISTINCT mcmraak_rivercrs_motorships.id, mcmraak_rivercrs_motorships.name
        FROM mcmraak_rivercrs_motorships
        INNER JOIN mcmraak_rivercrs_checkins
        ON mcmraak_rivercrs_checkins.motorship_id = mcmraak_rivercrs_motorships.id
        INNER JOIN mcmraak_rivercrs_pricing
        ON mcmraak_rivercrs_pricing.checkin_id = mcmraak_rivercrs_checkins.id
        */

        return DB::table('mcmraak_rivercrs_motorships')
        ->join('mcmraak_rivercrs_checkins',
               'mcmraak_rivercrs_checkins.motorship_id',
               '=',
               'mcmraak_rivercrs_motorships.id')
        ->join('mcmraak_rivercrs_pricing',
               'mcmraak_rivercrs_pricing.checkin_id',
               '=',
               'mcmraak_rivercrs_checkins.id')
        ->select(
               'mcmraak_rivercrs_motorships.id as id',
               'mcmraak_rivercrs_motorships.name as name'
                )
                ->distinct()
                ->orderBy('mcmraak_rivercrs_motorships.id')
                ->get();
    }

    public static function getMotorshipsWitchCheckins()
    {
        /* [SQL RAW ALL]
        SELECT DISTINCT mcmraak_rivercrs_motorships.id, mcmraak_rivercrs_motorships.name
        FROM mcmraak_rivercrs_motorships
        LEFT JOIN mcmraak_rivercrs_checkins
        ON mcmraak_rivercrs_checkins.motorship_id = mcmraak_rivercrs_motorships.id
        */

        return DB::table('mcmraak_rivercrs_motorships')
            ->join('mcmraak_rivercrs_checkins',
                'mcmraak_rivercrs_checkins.motorship_id',
                '=',
                'mcmraak_rivercrs_motorships.id')
            ->select(
                'mcmraak_rivercrs_motorships.id as id',
                'mcmraak_rivercrs_motorships.name as name'
            )
            ->distinct()
            ->orderBy('mcmraak_rivercrs_motorships.id')
            ->get();
    }


    # Save motorship price to dump
    public function setPriceAttribute($value)
    {
        $this->priceDump = $value;
    }

    # Save motorship price to DB
    public function savePrice()
    {
        $value = $this->priceDump;
        if(!$value) return;
        /* [ JSON Structure of record in records ]
        checkin_id (int)
        waybill (string)
        records
            checkin_id (int)
            cabin_id (int)
            cabin_name (string)
            price_a (int)
            price_b (int)
        */
        $value = json_decode($value);
        $count = count($value);
        if(count($value)) {
            foreach ($value as $chekinPrice){
                $checkin_id = $chekinPrice->checkin_id;
                $pricing_arr = $chekinPrice->records;
                Pricing::where('checkin_id', $checkin_id)->delete();
                $pricingTable = [];
                foreach( $pricing_arr as $v ){
                    $pricingTable[] = [
                        'checkin_id' => $checkin_id,
                        'cabin_id' => $v->cabin_id,
                        'price_a' => $v->price_a,
                        'price_b' => $v->price_b,
                    ];
                }
                Pricing::insert($pricingTable);
            }
        }
    }

    # Motorships "Techs" mutator
    public function setTechsAttribute($value)
    {
        $this->thisId = $this->id;
        $this->techsDump = $value;
    }

    # Motorships "Onboards" mutator
    public function setOnboardsAttribute($value)
    {
        $this->thisId = $this->id;
        $this->onboardsDump = $value;
    }

    # Motorships "Techs" save to BD
    public function saveTechs($id)
    {

        if(!$this->techsDump) return;
        TechsPivot::where('motorship_id', $id)->delete();

        $techs_arr = [];
        foreach ($this->techsDump as $value) {
            $techs_arr[] =
                [
                    'motorship_id' => $id,
                    'tech_id' => $value['tc'],
                    'value' => $value['vl'],
                ];
        }
        TechsPivot::insert($techs_arr);
    }

    # Motorships "Onboards" save to BD
    public function saveOnBorad($id)
    {
        if(!$this->onboardsDump) return;
        OnboardPivot::where('motorship_id', $id)->delete();
        $onboards_arr = [];
        if($this->onboardsDump) {
            foreach ($this->onboardsDump as $value) {
                $onboards_arr[] =
                    [
                        'motorship_id' => $this->id,
                        'onboard_id' => $value
                    ];
            }
            OnboardPivot::insert($onboards_arr);
        }
    }

    public function getYoutubeAttribute($value)
    {
        return json_decode($value, true);
    }
    public function setYoutubeAttribute($value)
    {
        $this->attributes['youtube'] = json_encode ($value, JSON_UNESCAPED_UNICODE);
    }

    public function recacheChekins()
    {
        return; // Method depricated
        $checkins = Checkins::where('motorship_id', $this->id)->get();
        foreach ($checkins as $checkin)
        {
            app('\Mcmraak\Rivercrs\Classes\Search')->delCache($checkin->id);
            $job_exist = DB::table('jobs')
                ->where('queue', 'RiverCrs.recacheChekin')
                ->where('payload', 'like', '%"checkin_id":'.$checkin->id.'%')
                ->count();

            if(!$job_exist) {
                Queue::push('\Mcmraak\Rivercrs\Classes\Search@renderCheckinJob', [
                    'checkin_id' => $checkin->id
                ], 'RiverCrs.recacheChekin');
            }
        }
    }


    function getPermanentDiscountsAttribute($value)
    {
        return json_decode($value, true);
    }

    function setPermanentDiscountsAttribute($value)
    {
        if(!$value) return;
        $this->attributes['permanent_discounts'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    function getPermanentDiscounts()
    {
        $discounts = $this->permanent_discounts;
        if(!$discounts) return;
        return collect($discounts)->map(function ($item) {
            $item['image'] = Config::get('cms.storage.media.path').$item['image'];
            return $item;
        })->toArray();
    }


    ########### EVENTS ###########


    # Checking the name of the motorship for emptiness
    public function beforeSave()
    {
        $user = BackendAuth::getUser();
        #if (!$user->hasAccess(['mcmraak.rivercrs.motorships.change']))
        #throw new ApplicationException('Вам запрещено изменять данные теплохода!');

        if(!$this->name)
        throw new ApplicationException('Введите название теплохода!');
    }

    public function beforeDelete()
    {
        $user = BackendAuth::getUser();
        if (!$user->hasAccess(['mcmraak.rivercrs.motorships.change']))
        throw new ApplicationException('Вам запрещено удалять теплоходы!');
    }

    # Motorships after save event (for ID)
    public function afterSave()
    {
        # Сохранять связные данные можно только из админки
        if(post('_token') === null) return;
        $this->saveTechs($this->id);
        $this->saveOnBorad($this->id);
        $this->savePrice();
        //$this->recacheChekins();
    }

    # Motorships after delete event
    public function afterDelete()
    {
         $id = $this->id;
         # Delete "OnBoard records"
         OnboardPivot::where('motorship_id', $id)->delete();
         # Delete "Techs records"
         TechsPivot::where('motorship_id', $id)->delete();

        $cabins = Cabins::where('motorship_id', $id)->get();
        $cabins_ids = [];
        foreach($cabins as $v){
            $cabins_ids[] = $v->id;
        }
        Cabins::where('motorship_id', $id)->delete();

        if($cabins_ids)
        IncabinPivot::whereIn('cabin_id', $cabins_ids)->delete();

        $checkins = Checkins::where('motorship_id', $id)->get();
        if($checkins){
            $checkins_ids = collect($checkins)->pluck('id');
            Checkins::where('motorship_id', $id)->delete();
            Waybills::whereIn('checkin_id', $checkins_ids)->delete();
            (new Search)->delCache($checkins_ids);
        }
    }
}
