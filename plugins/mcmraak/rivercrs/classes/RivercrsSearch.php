<?php

namespace Mcmraak\Rivercrs\Classes;

use Carbon\Carbon;
use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use DB;
use Input;
use View;

class RivercrsSearch
{
    # http://azimut.dc/rivercrs/api/search?debug=62
    public static function search()
    {
        $ids = Input::get('ids');
        $debug = input('debug');

        if ($debug) {
            $ids = explode(',', $debug);
        }

        if (!$ids) {
            return null;
        }

        /*
        $checkins = Checkin::whereIn('id', $ids)
            ->where('active', 1)
            ->orderBy('date')
            ->get();

        $output = [];

        foreach ($checkins as $checkin) {
            $cache_key = 'rcrs.'. $checkin->id;

            if (Cache::has($cache_key)) {
                $output[] = Cache::get($cache_key);
            } else {
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
                Cache::forever($cache_key, $result);
                $output[] = $result;
            }
        }

        */
        $output = [];
        foreach ($ids as $checkin_id) {
            $output[] = Checkin::getResult($checkin_id);
        }


        self::addTemporaryDiscounts($output);

        if ($debug) {
            dd($output);
        }

        return $output;

        # todo: deprecated by https://azimut-tour.atlassian.net/browse/RR-71
        # return View::make('mcmraak.rivercrs::rivercrs_checkins', ['checkins' => $output])->render();
    }

    # Добавить временные скидки
    public static function addTemporaryDiscounts(&$output = null, $ship_ids = null)
    {
        $ship_ids = $ship_ids ?? collect($output)->pluck('motorship_id')->unique()->toArray();

        $now_date = date('Y-m-d 00:00:00');
        $now_date_carbon = Carbon::parse($now_date);

        $discounts = DB::table('mcmraak_rivercrs_discounts')->where('valid_until', '>=', $now_date)
            ->where(function ($query) use ($ship_ids) {
                foreach ($ship_ids as $ship_id) {
                    $query->orWhere('motorships', 'like', "%#$ship_id#%");
                }
            })
            ->get();

        foreach ($output as &$item) {
            $item['temporary_discounts'] = [];
            foreach ($discounts as $discount) {
                if (strpos($discount->motorships, "#{$item['motorship_id']}#") !== false) {
                    self::addTemporaryDiscount($item, $discount, $now_date_carbon);
                }
            }
        }
    }

    # Добавить временную скидку
    private static function addTemporaryDiscount(&$item, $discount, $now_date)
    {
        $until = Carbon::parse($discount->valid_until);
        $before_until = $now_date->diffInDays($until, false);
        $title = ($before_until <= $discount->overlap_activation) ? $discount->overlap_title : $discount->title;
        $item['temporary_discounts'][] = [
            'image' => $discount->image,
            'title' => $title,
            'text' => $discount->desc,
        ];
    }
}
