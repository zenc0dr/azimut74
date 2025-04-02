<?php namespace Zen\Dolphin\Store;

use Zen\Dolphin\Classes\Core;
use Zen\Dolphin\Models\Extour;
use Zen\Dolphin\Models\Country;
use Zen\Dolphin\Models\Region;
use Zen\Dolphin\Models\City;
use Zen\Dolphin\Models\Place;
use Zen\Dolphin\Models\Tour;
use DB;
use Cache;

# Сформировать дерево гео-объектов
class GeoTree extends Core
{
    private array $extours;
    private array $places;

    public function get($options)
    {
        $cache_key = json_encode($options);
        if (Cache::has($cache_key)) {
            return Cache::get($cache_key);
        }


        $type = $options['type']; // type (ext||atm||atp) && dates (['dd.mm.YY',...])
        $type_id = $type === 'ext' ? 0 : 1;
        $dates = $options['dates'] ?? null;
        $exists = $this->getExistsGeo($dates, $type_id);

        if (!$exists) {
            return [];
        }

        $geo_exist = $exists['geo_exist'];
        $this->places = Place::getPlacesTable($exists['places']);
        $this->extours = Extour::getExtoursTable();

        $countries = Country::where(function ($q) use ($geo_exist) {
            if (!empty($geo_exist['countries'])) {
                $q->whereIn('id', $geo_exist['countries']);
            }
        })->orderBy('sort_order')->lists('name', 'id');

        $regions = [];
        $regionsModel = Region::where(function ($q) use ($geo_exist) {
            if (!empty($geo_exist['regions'])) {
                $q->whereIn('id', $geo_exist['regions']);
            }
        })->orderBy('sort_order')->get();

        foreach ($regionsModel as $region) {
            $regions[$region->id] = [
                'name' => $region->name,
                'country_id' => $region->country_id,
                'items' => []
            ];
        }

        $cities = [];
        $citiesModel = null;

        if ($geo_exist == null) {
            $citiesModel = City::orderBy('sort_order')->get();
        } else {
            if ($geo_exist['cities']) {
                $citiesModel = City::where(function ($q) use ($geo_exist) {
                    if (@$geo_exist['cities']) {
                        $q->whereIn('id', $geo_exist['cities']);
                    }
                })->orderBy('sort_order')->get();
            }
        }

        if ($citiesModel) {
            foreach ($citiesModel as $city) {
                $cities[$city->id] = [
                    'name' => $city->name,
                    'region_id' => $city->region_id,
                ];
            }
        }

        foreach ($cities as $id => $city) {
            $regions[$city['region_id']]['items'][$id] = $city['name'];
        }

        $tree = [];
        foreach ($regions as $id => $region) {
            if (empty($region['country_id'])) {
                continue;
            }

            if (!isset($tree[$region['country_id']])) {
                $tree[$region['country_id']] = [
                    'name' => $countries[$region['country_id']],
                    'items' => []
                ];
            }
            $tree[$region['country_id']]['items'][$id] = $region;
            unset($tree[$region['country_id']]['items'][$id]['country_id']);
        }

        # Рекурсивно проставим id-шники и упорядочим ключи
        $tree = $this->stampId($tree);
        $this->sortGeoTree($tree);

        Cache::add($cache_key, $tree, 180);

        return $tree;
    }

    public function getExistsGeo($dates, $type_id): array
    {
        if ($type_id !== 0 && !$dates) {
            return [];
        }

        if ($dates) {
            foreach ($dates as &$date) {
                $date = $this->dateToMysql($date);
            }
        }

        DB::unprepared("SET sql_mode = ''");
        $waybills = DB::table('zen_dolphin_tours as tour')
            ->whereNotNull('tour.waybill')
            ->where('tour.type_id', $type_id)
            ->join(
                'zen_dolphin_prices as price',
                'price.tour_id', '=', 'tour.id'
            )
            ->groupBy('tour.id')
            ->select(
                'tour.waybill as waybill'
            );

        if ($dates) {
            $waybills->whereIn('price.date', $dates);
        }

        $waybills = $waybills->pluck('waybill')->toArray();

        $geo_codes = [];

        foreach ($waybills as $waybill) {
            $waybill = explode(' ', $waybill);

            if (count($waybill) < 3) {
                continue;
            }

            unset($waybill[0]);
            unset($waybill[count($waybill)]);

            foreach ($waybill as $geo_code) {
                $geo_codes[$geo_code] = null;
            }
        }

        $geo_codes = array_keys($geo_codes);

        $exist_codes = [
            'countries' => [],
            'regions' => [],
            'cities' => [],
        ];

        $places = [];
        foreach ($geo_codes as $code) {
            $code = explode(':', $code);
            if (strpos($code[1], 'ps') !== false) {
                $id = preg_replace('/\D+/', '', $code[1]);
                $places[$id] = null;
                continue;
            }
            if ($code[0] == 0) {
                $exist_codes['countries'][$code[1]] = null;
            }
            if ($code[0] == 1) {
                $exist_codes['regions'][$code[1]] = null;
            }
            if ($code[0] == 2) {
                $exist_codes['cities'][$code[1]] = null;
            }
        }

        if ($places) {
            $places = array_keys($places);
            $places_geo_codes = Place::whereIn('id', $places)->pluck('geo_code')->toArray();

            foreach ($places_geo_codes as $code) {
                $code = explode(':', $code);
                if ($code[0] == 0) {
                    $exist_codes['countries'][$code[1]] = null;
                }
                if ($code[0] == 1) {
                    $exist_codes['regions'][$code[1]] = null;
                }
                if ($code[0] == 2) {
                    $exist_codes['cities'][$code[1]] = null;
                }
            }
        }

        if ($exist_codes['countries']) {
            $exist_codes['countries'] = array_keys($exist_codes['countries']);
        }
        if ($exist_codes['regions']) {
            $exist_codes['regions'] = array_keys($exist_codes['regions']);
        }
        if ($exist_codes['cities']) {
            $exist_codes['cities'] = array_keys($exist_codes['cities']);
        }

        $regions_ids = City::whereIn('id', $exist_codes['cities'])->pluck('region_id')->toArray();
        $exist_codes['regions'] = array_merge($exist_codes['regions'], $regions_ids);
        $exist_codes['regions'] = array_unique($exist_codes['regions']);

        $counties_ids = Region::whereIn('id', $exist_codes['regions'])->pluck('country_id')->toArray();
        $exist_codes['countries'] = array_merge($exist_codes['countries'], $counties_ids);
        $exist_codes['countries'] = array_unique($exist_codes['countries']);

        return [
            'places' => $places,
            'geo_exist' => $exist_codes
        ];
    }

    private function stampId($array, $level = 0): array
    {
        $newArray = [];
        foreach ($array as $key => $val) {
            $level_key = $level . ':' . $key;

            if (is_array($val)) {
                $val['id'] = $level_key;
                if (isset($val['items'])) {
                    $val['items'] = $this->stampId($val['items'], $level + 1);
                }

                if ($this->extours) {
                    $this->addExtours($val);
                }
                if ($this->places) {
                    $this->addPlaces($val);
                }

                $newArray[] = $val;
            } else {
                $insert = [
                    'id' => $level_key,
                    'name' => $val,
                ];

                # Добавим 4-ый уровень
                $this->addPlacesItems($insert);

                $newArray[] = $insert;
            }
        }
        return $newArray;
    }

    public function addExtours(&$item)
    {
        if (isset($this->extours[$item['id']])) {
            $extours = $this->extours[$item['id']];
            $item['items'] = array_merge($extours, $item['items']);
        }
    }

    public function addPlaces(&$item)
    {
        if (isset($this->places[$item['id']])) {
            $places = $this->places[$item['id']];
            $item['items'] = array_merge($places, $item['items']);
        }
    }

    # Функция добавления 4-ого уровня items (для городов)
    public function addPlacesItems(&$item)
    {
        if (isset($this->places[$item['id']])) {
            $item['items'] = $this->places[$item['id']];
        }
    }

    private function sortGeoTree(&$tree)
    {
        foreach ($tree as &$country) {
            if (isset($country['items'])) {
                $country['items'] = array_values(collect($country['items'])->sortBy('name')->toArray());
                foreach ($country['items'] as &$region) {
                    if (isset($region['items'])) {
                        $region['items'] = array_values(collect($region['items'])->sortBy('name')->toArray());
                        foreach ($region['items'] as &$city) {
                            if (isset($city['items'])) {
                                $city['items'] = array_values(collect($city['items'])->sortBy('name')->toArray());
                            }
                        }
                    }
                }
            }
        }
    }
}
