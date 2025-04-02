<?php namespace Mcmraak\Rivercrs\Classes;

use Input;
use DB;
use Mcmraak\Rivercrs\Classes\CacheSettings as Settings;
use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use Cache;
use Log;
use View;

class Search
{
    public $startTownName = 'Саратов';

    public function json($array)
    {
        echo json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function mounted()
    {
        # Кеширование
        $filter_cache = Settings::get('filter_cache');
        if ($filter_cache && Cache::has('rivercrs.FilterDATA')) {
            return Cache::get('rivercrs.FilterDATA');
        }

        # Города (checkins=3147)
        $data = DB::table('mcmraak_rivercrs_checkins as checkin')->
        where('checkin.active', 1)->
        where('town.active', 1)->
        join(
            'mcmraak_rivercrs_waybills as way',
            'way.checkin_id',
            '=',
            'checkin.id'
        )->
        join(
            'mcmraak_rivercrs_motorships as ship',
            'ship.id',
            '=',
            'checkin.motorship_id'
        )->
        join(
            'mcmraak_rivercrs_towns as town',
            'town.id',
            '=',
            'way.town_id'
        )->
        select(
            'checkin.id as checkin_id',
            'checkin.date as date',
            'checkin.days as days',
            'ship.id as ship_id',
            'ship.name as ship_name',
            'ship.extra_name as ship_extra_name',
            'town.id as town_id',
            'town.name as town_name',
            'town.alt_name as town_alt_name',
            'town.soft_id as town_soft_id',
            'way.order as order'
        )->
        //orderBy('checkin.id')->
        //orderBy('checkin.date')->
        //orderBy('way.order')->
        get();

        $result = [];

        foreach ($data as $item) {
            $result['checkins'][$item->checkin_id] = [
                'ship_id' => $item->ship_id,
                'date' => $this->dateFormatter($item->date),
                'days' => $item->days,
            ];
            $result['ways'][] = [
                'checkin_id' => $item->checkin_id,
                'town_id' => $item->town_id,
                'start' => ($item->order) ? 0 : 1,
            ];
            $town_name = empty($item->town_alt_name)
                ? $item->town_name
                : $item->town_alt_name;

            $town_name = trim($town_name);

            $ship_name = !empty($item->ship_extra_name)
                ? $item->ship_extra_name
                : $this->cleanShipQuotes($item->ship_name);

            $result['towns'][$item->town_id] = $town_name;
            $result['ships'][$item->ship_id] = $ship_name;
            if ($item->town_soft_id) {
                $result['soft'][$item->town_id] = $item->town_soft_id;
            }
        }

        uasort($result['checkins'], function ($a, $b) {
            return ($a['date'] - $b['date']);
        });

        $newArr = [];
        foreach ($result['checkins'] as $id => $item) {
            $newArr[] = [
                'id' => $id,
                'ship_id' => $item['ship_id'],
                'date' => $item['date'],
                'days' => $item['days']
            ];
        }

        $result['checkins'] = $newArr;
        $result['start_id'] = 63; // Саратов

        $json = json_encode($result, JSON_UNESCAPED_UNICODE);

        if ($filter_cache) {
            Cache::add('rivercrs.FilterDATA', $json, CacheSettings::get('filter_cache'));
        }
        return $json;
    }

    # Выбирает из строки имя теплохода (Которое в кавычках)
    public function cleanShipQuotes($string)
    {
        preg_match('/"(.+)"/', $string, $match);
        return isset($match[1]) ? $match[1] : $string;
    }

    # Убирает из даты время и переводит дату в секунды
    public function dateFormatter($mysqlDate)
    {
        $mysqlDate = substr($mysqlDate, 0, 10);
        return strtotime($mysqlDate);
    }

    public function search()
    {
        $ids = Input::get('ids');
        if (!$ids) {
            return;
        }
        $render = $this->renderCheckinsBlocks($ids);
        $this->json([
            'html' => $render
        ]);
    }

    public function renderCheckinsBlocks($ids = [], $update = false)
    {
        $checkins = Checkin::whereIn('id', $ids)->
        where('active', 1)->
        orderBy('date')->
        get();

        $html = '';
        foreach ($checkins as $checkin) {
            $html .= $this->renderCheckin($checkin, [
                'weekdays' => ['вс', 'пн', 'вт', 'ср', 'чт', 'пт', 'сб'],
                'btns' => Settings::get('booking_discounts_btn'),
                'environment' => \App::environment(),
                'update' => $update
            ]);
        }
        return $html;
    }


    public function renderCheckinJob($job, $data)
    {
        $this->renderCheckin($data['checkin_id'], ['update' => true]);
        $job->delete();
    }

    public function renderCheckin($checkin, $opts = [])
    {
        return; // method deprecated

        if (!$checkin) {
            return;
        }
        if (!is_object($checkin)) $checkin = Checkin::find($checkin);

        $update = (isset($opts['update'])) ? $opts['update'] : false;

        if (!$update) {
            $cache = $this->getCache($checkin->id);
            if ($cache) return $cache;
        }

        $weekdays = (isset($opts['weekdays'])) ? $opts['weekdays'] : ['вс', 'пн', 'вт', 'ср', 'чт', 'пт', 'сб'];
        $btns = (isset($opts['btns'])) ? $opts['btns'] : Settings::get('booking_discounts_btn');
        $environment = (isset($opts['environment'])) ? $opts['environment'] : \App::environment();

        $render = \View::make('mcmraak.rivercrs::checkin_block', [
            'checkin' => $checkin,
            'weekdays' => $weekdays,
            'booking_discounts_btn' => $btns,
            'environment' => $environment
        ]);

        $html = $render->render();

        if ($checkin)
            $this->putCache($checkin->id, $html);

        if (!$update) return $html;
        return true;
    }

    public function getCache($checkin_id)
    {
        $cache_filename = base_path() . '/storage/rivercrs_cache/checkins/' . $checkin_id . '.gz';
        if (file_exists($cache_filename)) {
            $data = file_get_contents($cache_filename);
            $data = substr($data, 10, -8);
            return gzinflate($data);
        }
        return false;
    }

    public function putCache($checkin_id, $html)
    {
        if (!file_exists(base_path() . '/storage/rivercrs_cache/checkins')) {
            mkdir(base_path() . '/storage/rivercrs_cache/checkins');
        }
        $cache_filename = base_path() . '/storage/rivercrs_cache/checkins/' . $checkin_id . '.gz';
        $data = gzencode($html, 9);
        file_put_contents($cache_filename, $data);
    }

    public function delCache($checkin_id)
    {
        if (is_array($checkin_id)) {
            foreach ($checkin_id as $id) {
                $cache_filename = base_path() . '/storage/rivercrs_cache/checkins/' . $id . '.gz';
                @unlink($cache_filename);
            }
            return;
        }

        $cache_filename = base_path() . '/storage/rivercrs_cache/checkins/' . $checkin_id . '.gz';
        @unlink($cache_filename);
    }

}
