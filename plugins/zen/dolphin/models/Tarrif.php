<?php namespace Zen\Dolphin\Models;

use Model;
use DB;
use Zen\Dolphin\Classes\Core;
use BackendAuth;

/**
 * Model
 */
class Tarrif extends Model
{
    use \October\Rain\Database\Traits\Validation;
    public $table = 'zen_dolphin_tarrifs';

    public $rules = [
    ];

    public $belongsTo = [
        'tour' => [
            Tour::class
        ],
        'operator' => [
            Operator::class
        ],
        'azcomfort' => [
            Azcomfort::class
        ],
        'azpansion' => [
            Azpansion::class
        ],
        'hotel' => [Hotel::class]
    ];

    function getOperatorNameAttribute()
    {
        if(!BackendAuth::check()) return;
        return @$this->operator->name;
    }

    function getPricesAttribute()
    {
        $prices = DB::table('zen_dolphin_prices')
            ->where('tarrif_id', $this->id)
            ->orderBy('azroom_id')
            ->orderBy('pricetype_id')
            ->orderBy('date')
            ->get();

        $core = new Core;

        $return = [];
        foreach ($prices as $price) {
            $date = $core->dateFromMysql($price->date);
            if(!isset($return[$date])) $return[$date] = [];
            $return[$date][] = [
                'azroom_id' => $price->azroom_id,
                'pricetype_id' => $price->pricetype_id,
                'ages' => ($price->age_min) ? "{$price->age_min}-{$price->age_max}" : '',
                'price' => $price->price,
            ];
        }
        return $return;
    }


    private $pricesData;
    function setPricesAttribute($data)
    {
        $this->pricesData = $data;
    }

    function setImportAttribute($value)
    {
        $this->import = $value;
    }

    function getReductDatesAttribute()
    {
        if(!$this->reduct_id) return;
        $reduct_dates = DB::table('zen_dolphin_reducts_pivot')
            ->where('tarrif_id', $this->id)
            ->get();
        $core = new Core;
        $output = [];
        foreach ($reduct_dates as $record) {
            $output[] = $core->dateFromMysql($record->date);
        }
        return $output;
    }

    private $reductDatesDump;
    function setReductDatesAttribute($reduct_dates)
    {
        $this->reductDatesDump = $reduct_dates;
    }

    function savePrices()
    {
        DB::table('zen_dolphin_prices')
            ->where('tarrif_id', $this->id)
            ->delete();

        if($this->import) return;

        $core = new Core;

        $insert = [];
        foreach ($this->pricesData as $date => $items)
        {
            foreach ($items as $item)
            {
                $ages = @$item['ages'];

                if($ages) $ages = explode('-', $ages);

                $insert[] = [
                    'date' => $core->dateToMysql($date),
                    'tour_id' => $this->tour_id,
                    'tarrif_id' => $this->id,
                    'azroom_id' => $item['azroom_id'],
                    'pricetype_id' => $item['pricetype_id'],
                    'age_min' => ($ages)?$ages[0]:null,
                    'age_max' => ($ages)?$ages[1]:null,
                    'price' => @$item['price'] ?? 0
                ];
            }
        }

        DB::table('zen_dolphin_prices')->insert($insert);
    }

    function getConflict()
    {
        $tour_id = $this->tour_id;
        $tarrif_id = $this->id;
        $reduct_id = $this->reduct_id;
        $reduct_dates = $this->reduct_dates;

        $core = new Core;

        $reduct_dates = collect($reduct_dates)->map(function ($date) use ($core) {
            return $core->dateToMysql($date);
        })->toArray();


        $pivot = DB::table('zen_dolphin_tours as tour')
            ->where('tour.id', $tour_id)
            ->whereIn('pivot.date', $reduct_dates)
            ->join('zen_dolphin_tarrifs as tarrif', 'tarrif.tour_id', '=', 'tour.id')
            ->join('zen_dolphin_reducts_pivot as pivot', 'pivot.tarrif_id', '=', 'tarrif.id')
            ->select(
                'tour.id as tour_id',
                'tarrif.id as tarrif_id',
                'pivot.date as date',
                'pivot.reduct_id as reduct_id'
            )

            ->get();

        $output = [];

        foreach ($pivot as $record) {
            if($record->tarrif_id == $tarrif_id || $record->reduct_id == $reduct_id) continue;
            $date = $core->dateFromMysql($record->date);
            $output[] = "Тариф#{$record->tarrif_id} [$date]";
        }

        if(!$output) return;

        return "Конфликт скидок: " . join(', ', $output);
    }

    private function saveReductDates()
    {
        DB::table('zen_dolphin_reducts_pivot')->where('tarrif_id', $this->id)->delete();
        if(!$this->reduct_id || !$this->reductDatesDump) return;

        $core = new Core;
        $insert = [];
        foreach ($this->reductDatesDump as $date) {
            $insert[] = [
                'date' => $core->dateToMysql($date),
                'tarrif_id' => $this->id,
                'reduct_id' => $this->reduct_id
            ];
        }
        DB::table('zen_dolphin_reducts_pivot')->insert($insert);
    }

    function afterSave()
    {
        $this->savePrices();
        $this->saveReductDates();
    }

    function afterDelete()
    {
        DB::table('zen_dolphin_prices')
            ->where('tarrif_id', $this->id)
            ->delete();
    }
}
