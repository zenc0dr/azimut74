<?php namespace Mcmraak\Rivercrs\Models;

use Model;
use October\Rain\Exception\ApplicationException;
use Carbon\Carbon;
use DB;
use Log;
use Mcmraak\Rivercrs\Classes\CacheSettings;
use View;
use Cache;
use Zen\Cabox\Classes\Cabox;

/**
 * Model
 */
class Checkins extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /*
     * Disable timestamps by default.
     * Remove this line if timestamps are defined in the database table.
     */
    public $timestamps = false;

    /*
     * Validation
     */
    public $rules = [
    ];

    ### Dumps before save
    public $thisId; // for detect id and model method (create or update)
    public $waybillDump; // for saving after create
    public $duplicatorId;
    public $pricingTableDump;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'mcmraak_rivercrs_checkins';

    # Relationships
    public $belongsTo = [
        'motorship' => 'Mcmraak\Rivercrs\Models\Motorships',
    ];

    # for filter by eds_code
    public function scopeFilterEds($query, $text)
    {
        $text = CacheSettings::edsNameToCode($text);

        return $query->where('eds_code', $text);
    }

    # for filter by eds_id
    public function scopeFilterEdsid($query, $text)
    {
        return $query->where('eds_id', $text);
    }

    public function scopeFilterId($query, $text)
    {
        return $query->where('id', $text);
    }

    # Названия источников из настроек
    public function getEdsNameAttribute()
    {
        return CacheSettings::edsCodeToName($this->eds_code);
    }

    # waybill_path for selector
    public function getWaybill($separator = false)
    {
        return Waybills::GetWaybillPath($this->id, 0, 0, $separator);
    }

    public function getWaybillShort($separator = false)
    {
        return Waybills::GetWaybillPath($this->id, 0, 1, $separator);
    }

    public function getWaybillLine()
    {
        return Waybills::GetWaybillPath($this->id, 1, 0, ' - ');
    }

    public function getWaybillCleanArr()
    {
        $waybill_items = \DB::table('mcmraak_rivercrs_waybills as way')
            ->where('way.checkin_id', $this->id)
            ->leftJoin(
                'mcmraak_rivercrs_towns as town',
                'town.id',
                '=',
                'way.town_id'
            )
            ->leftJoin(
                'mcmraak_rivercrs_towns as alt',
                'alt.id',
                '=',
                'town.soft_id'
            )
            ->select([
                'town.name as name',
                'alt.name as alt_name',
            ])
            ->orderBy('way.order')
            ->get();

        return $waybill_items->map(function ($item) {
            return $item->alt_name ?? $item->name;
        })->toArray();
    }

    public function waybillKey()
    {
        $ways = Waybills::where('checkin_id', $this->id)
            ->orderBy('order')
            ->pluck('town_id')
            ->toArray();
        return join('-', $ways);
    }

    public function getWaybillCountAttribute()
    {
        return Waybills::where('checkin_id', $this->id)->count();
    }

    public function getWaybillArrayAttribute()
    {
        return Waybills::where('checkin_id', $this->id)->get();
    }

    public function getJsonDataAttribute()
    {
        $waybill = $this->waybill_array;
        $return = [
            'town_id' => $waybill[0]->town_id,
        ];
        return json_encode($return, JSON_UNESCAPED_UNICODE);
    }

    public function getInfoAttribute()
    {
        return '[id: ' . $this->id . '] ' . $this->getWaybillShort();
    }

    public function getPrice()
    {
        return Pricing::CheckinPrice($this->id, $this->motorship_id);
    }

    # for filter of motorshis in cabins field list
    public function scopeFilterMotorships($query, $motorship)
    {
        return $query->whereHas('motorship', function ($q) use ($motorship) {
            $q->whereIn('id', $motorship);
        });
    }

    # Get list for dropdown of motorships field in cabin form
    public function getMotorshipOptions()
    {
        return Motorships::lists('name', 'id');
    }

    # Save value from dropdown of motorships field in cabin form
    public function setMotorshipAttribute($value)
    {
        $this->motorship_id = $value;
    }

    # Get data for repeater items (Waybill points list)
    public function getWaybillIdAttribute()
    {
        $waybill = Waybills::where('checkin_id', $this->id)->orderBy('order')->get();
        $repeater = [];
        foreach ($waybill as $value) {
            $repeater[] = [
                'town' => $value->town_id,
                'excursion' => $value->excursion,
                'bold' => $value->bold
            ];
        }
        return $repeater;
    }

    # Get towns list
    public function getTownOptions()
    {
        return Towns::orderBy('name')->lists('name', 'id');
    }

    public function setWaybillIdAttribute($value)
    {
        $this->thisId = $this->id;
        $this->waybillDump = $value;

    }

    # Save pricing table to dump
    public function setPricingAttribute($value)
    {
        $this->pricingTableDump = $value;
        //throw new ApplicationException(print_r($value));
    }

    # Save pricing table to database
    public function savePricingTable()
    {
        $pricingTableDump = $this->pricingTableDump;
        if (!$pricingTableDump) return;
        $pricingTableDump = json_decode($pricingTableDump);
        Pricing::where('checkin_id', $this->id)->delete();
        $pricingTable = [];
        foreach ($pricingTableDump as $v) {
            $pricingTable[] = [
                'checkin_id' => $this->id,
                'cabin_id' => $v->cabin_id,
                'price_a' => $v->price_a,
                'price_b' => $v->price_b,
            ];
        }
        Pricing::insert($pricingTable);
    }

    public function getDuplicatorOptions()
    {
        $return = [];
        $checkins = $this->get();
        foreach ($checkins as $checkin) {
            $return[$checkin->id] = 'Заезд id: ' . $checkin->id;
        }
        return [0 => 'Не копировать'] + $return;
    }

    public function setDuplicatorAttribute($value)
    {
        if (!$value) return;
        $this->duplicatorId = $value;
    }

    public function saveWaybill()
    {
        if ($this->waybillDump == 'none') return;
        if (!$this->waybillDump)
            throw new ApplicationException('Отсутствует маршрут!');
        if (count($this->waybillDump) < 2)
            throw new ApplicationException('Маршрут должен состоять из более чем одного пункта!');
        Waybills::where('checkin_id', $this->id)->delete();
        $order_cnt = 0;
        foreach ($this->waybillDump as $v) {
            $arr[] =
                [
                    'checkin_id' => $this->id,
                    'town_id' => $v['town'],
                    'order' => $order_cnt,
                    'excursion' => $v['excursion'],
                    'bold' => $v['bold'],
                ];
            $order_cnt++;
        }
        Waybills::insert($arr);
    }

    public function saveDuplicator()
    {
        Waybills::where('checkin_id', $this->id)->delete();
        $waybills = Waybills::where('checkin_id', $this->duplicatorId)->get();
        $arr = [];
        $order_cnt = 0;
        foreach ($waybills as $v) {
            $arr[] =
                [
                    'checkin_id' => $this->id,
                    'town_id' => $v->town_id,
                    'order' => $v->order,
                    'excursion' => $v->excursion,
                    'bold' => $v->bold,
                ];
            $order_cnt++;
        }
        Waybills::insert($arr);
    }

    public function volgaSchelude()
    {
        return \Mcmraak\Rivercrs\Controllers\VolgaSettings::getVolgaExcursion($this);
    }

    public function germesSchelude()
    {
        return app('\Mcmraak\Rivercrs\Classes\Exist\GermesSchelude')->render($this);
    }

    function updateSearchCache()
    {
        return; // method deprecated
        app('\Mcmraak\Rivercrs\Classes\Search')->renderCheckin($this, ['update' => true]);
    }

    function getStartPriceAttribute()
    {
        $min_price_a = DB::table('mcmraak_rivercrs_pricing')
            ->where('checkin_id', $this->id)
            ->where('price_a', '<>', 0)
            ->orderBy('price_a')
            ->first()->price_a ?? 0;

        $min_price_b = DB::table('mcmraak_rivercrs_pricing')
            ->where('checkin_id', $this->id)
            ->where('price_b', '<>', 0)
            ->orderBy('price_b')
            ->first()->price_b ?? 0;

        $alt_price = pricePatch()->getMinPrice($this->id);

        if ($alt_price && $alt_price > $min_price_a) {
            $min_price_a = $alt_price;
        }

        $min_price_a = intval($min_price_a);
        $min_price_b = intval($min_price_b);

        if ($min_price_b === 0) {
            return $min_price_a;
        }
        if ($min_price_a === 0) {
            return $min_price_b;
        }

        return ($min_price_a < $min_price_b) ? $min_price_a : $min_price_b;
    }

    # TODO: Под вопросом
//    function getRenderKruizAttribute()
//    {
//
//        $output = [
//            'id' => $this->id,
//            'image' => $this->motorship->pic,
//            'motorship_name' => $this->motorship->alt_name,
//            'date' => $this->createDatesArray(),
//            'waybill' => $this->getWaybill(' - '),
//            'price_start' => $this->startPrice,
//            'days' => $this->days . ' ' . $this->incline(['день', 'дня', 'дней'], $this->days)
//        ];
//
//        $html =  View::make('mcmraak.rivercrs::prok.checkin', ['checkin' => $output])->render();
//        //die($html);//2942
//        return $html;
//    }

    function getTemporaryDiscountsAttribute()
    {
        return $this->motorship->temporary_discounts;
    }

    # Склонение существительных ex: incline(['комментарий','комментария','комментариев'], 5)
    function incline($words, $n)
    {
        if ($n % 100 > 4 && $n % 100 < 20) {
            return $words[2];
        }
        $a = array(2, 0, 1, 1, 1, 2);
        return $words[$a[min($n % 10, 5)]];
    }

    function createDatesArray()
    {
        $dow = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
        $date_1 = Carbon::parse($this->date);
        $date_1_dow = $dow[$date_1->dayOfWeek];
        $date_1_formated = explode('|', $date_1->format('d.m.Y|H:i'));

        $date_2 = Carbon::parse($this->dateb);
        $date_2_dow = $dow[$date_2->dayOfWeek];
        $date_2_formated = explode('|', $date_2->format('d.m.Y|H:i'));
        return [
            'd1' => $date_1_formated[0],
            't1' => $date_1_formated[1],
            'd1d' => $date_1_dow,
            'd2' => $date_2_formated[0],
            't2' => $date_2_formated[1],
            'd2d' => $date_2_dow
        ];
    }

    public static function getResult($checkin_id, $clear = false)
    {
        $cache_key = 'rcrs:' . $checkin_id;

        $cabox = new Cabox('rivercrs');
        $cache = $cabox->get($cache_key);

        if (!$clear && $cache) {
            return $cache;
        }

        $checkin = self::find($checkin_id);
        if (!$checkin) {
            return null;
        }

        $ship = $checkin->motorship;

        $result = [
            'id' => $checkin->id,
            'image' => $ship->pic,
            'youtube' => $ship->youtube_link,
            'motorship_id' => $ship->id,
            'motorship_name' => $ship->alt_name,
            'motorship_status' => $ship->status->name ?? null,
            'motorship_status_desc' => $ship->status->desc ?? null,
            'permanent_discounts' => $ship->permanent_discounts,
            'date' => $checkin->createDatesArray(),
            'waybill' => $checkin->getWaybill(' - '),
            'price_start' => $checkin->startPrice,
            'days' => $checkin->days . ' ' . $checkin->incline(['день', 'дня', 'дней'], $checkin->days),
        ];

        //Cache::add($cache_key, $result, 1440);
        $cabox->put($cache_key, $result);
        return $result;
    }

    public function cachePrices()
    {
        app('Mcmraak\Rivercrs\Classes\Exist')->get($this, 'array');
    }



    ## EVENTS

    # Check requred data in
    public function beforeSave()
    {
        if (!$this->date) {
            throw new ApplicationException('Не указаны дата и время отправления!');
        }
        if (!$this->dateb) {
            throw new ApplicationException('Не указаны дата и время прибытия!');
        }

        if (strtotime($this->dateb) < strtotime($this->date)) {
            throw new ApplicationException('Время прибытия не может быть раньше времени отправления!');
        }

        $start = strtotime($this->date);
        $start = date('Y-m-d', $start);

        $end = strtotime($this->dateb);
        $end = date('Y-m-d', $end);

        $start = Carbon::parse($start);
        $end = Carbon::parse($end);
        $diff = $end->diffInDays($start);
        $diff++;
        $this->days = $diff;

        if (!$this->days) {
            throw new ApplicationException('Количество дней тура не может быть нулевым');
        }
    }

    # Checkin after save event (for ID)
    public function afterSave()
    {
        if ($this->duplicatorId) {
            $this->saveDuplicator();
        } else {
            $this->saveWaybill();
        }
        $this->savePricingTable();

        # Изготовление кеша результата запроса
        /*
        if(DB::table('mcmraak_rivercrs_pricing')->where('checkin_id', $this->id)->count())
            app('\Mcmraak\Rivercrs\Classes\Search')->renderCheckin($this,['update' => true]);
        */
        self::getResult($this->id, true);
        $this->cachePrices();
    }

    public function afterDelete()
    {
        Pricing::where('checkin_id', $this->id)->delete();
        Waybills::where('checkin_id', $this->id)->delete();
        //app('\Mcmraak\Rivercrs\Classes\Search')->delCache($this->id);
        $cache_key = 'rcrs:' . $this->id;
        $cabox = new Cabox('rivercrs');
        $cabox->del($cache_key);
    }

}
