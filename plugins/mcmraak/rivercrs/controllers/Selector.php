<?php namespace Mcmraak\Rivercrs\Controllers;

use DB;
use Mcmraak\Rivercrs\Classes\CacheSettings;
use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use Cache;

class Selector
{
    public static $startTownName = 'Саратов';

    public static function dateFormatter($mysqlDate)
    {
        $mysqlDate = substr($mysqlDate,0,10);
        return strtotime($mysqlDate);
    }

    public static function filter()
    {
        $filter_cache = CacheSettings::get('filter_cache');

        if ($filter_cache && Cache::has('rivercrs.FilterDATA')) {
            return Cache::get('rivercrs.FilterDATA');
        }

        # Города
        $data = DB::table('mcmraak_rivercrs_waybills as way')->
            where('checkin.active',1)->
            leftJoin(
                'mcmraak_rivercrs_towns as town',
                'town.id',
                '=',
                'way.town_id'
                )->
            leftJoin(
                'mcmraak_rivercrs_checkins as checkin',
                'checkin.id',
                '=',
                'way.checkin_id'
                )->
            leftJoin(
                'mcmraak_rivercrs_motorships as ship',
                'ship.id',
                '=',
                'checkin.motorship_id'
                )->
            select(
                'town.id as town_id',
                'town.name as town_name',
                'town.soft_id as town_soft_id', // id
                'way.order as order',
                'ship.id as ship_id',
                'ship.name as ship_name',
                'checkin.id as checkin_id',
                'checkin.date as date',
                'checkin.days as days'
                )->
            orderBy('checkin.id')->
            orderBy('checkin.date')->
            orderBy('way.order')->
            get();

        $result = [];
        $parent = 0;
        foreach ($data as $v) {
            $town = trim($v->town_name);
            if($town == self::$startTownName){
                $result['start_id'] = $v->town_id;
            }
            if($v->order == 0){
                $parent = $v->town_id;
                $result['towns'][$v->town_id] = $town;
            }
            if($v->order != 0){
                $result['dests'][] = [
                    'parent' => $parent,
                    'id' => $v->town_id,
                    'name' => $town,
                    'checkin_id' => $v->checkin_id,
                ];
            }
            if($v->town_soft_id) {
                $result['soft'][$v->town_id.':'.$v->town_soft_id] = null;
            }
            $result['checkins'][$v->checkin_id] = [
                'ship_id' => $v->ship_id,
                'date' => self::dateFormatter($v->date),
                'days' => $v->days,
            ];
            $result['ships'][$v->ship_id] = \Mcmraak\Rivercrs\Models\Motorships::cleanName($v->ship_name);

        }

        $soft = [];
        foreach ($result['soft'] as $item => $null)
        {
            $soft[] = $item;
        }

        $result['soft'] = $soft;

        //dd($result['soft']);

        $json = json_encode ($result, JSON_UNESCAPED_UNICODE);

        if($filter_cache) Cache::add('rivercrs.FilterDATA', $json, CacheSettings::get('filter_cache'));
        return $json;
    }

    /**
     * @input: $input (array)
     *         $input['checkins'] = 'all' or '1+2+3+4+5' checkins id
     *         $input['page'] = number of pagination page
    */
    public static function idsQueryString($input=null)
    {

        if(!isset($input['checkins'])) return;
        $ids = $input['checkins'];
        if(!isset($input['page'])) $input['page'] = 0;

        $filter_cache = CacheSettings::get('filter_cache');
        $cache_key = 'rivercrs.results='.md5($ids).'page='.$input['page'];

        if ($filter_cache && Cache::has($cache_key)) {
            return Cache::get($cache_key);
        }


        $checkins = Checkin::where(function($query) use ($ids){
                if($ids !== 'all') {
                    $ids = explode("+", $ids);
                    $query->whereIn('id', $ids);
                }
            })->
            where('active',1)->
            orderBy('date')->
            paginate(15);

        if($input['page']>1) $checkins->currentPage();
        $checkins->setPath('#ajax');
        $weekdays = ['вс','пн','вт','ср','чт','пт','сб'];

        $booking_discounts_btn = \Mcmraak\Rivercrs\Classes\CacheSettings::get('booking_discounts_btn');

        $render = \View::make('mcmraak.rivercrs::checkins',[
            'checkins' => $checkins,
            'weekdays'=>$weekdays,
            'booking_discounts_btn' => $booking_discounts_btn,
            'environment' => \App::environment()
        ]);

        $render = $render->render();

        if($filter_cache) Cache::add($cache_key, $render, CacheSettings::get('filter_cache'));

        return $render;

    }

}
