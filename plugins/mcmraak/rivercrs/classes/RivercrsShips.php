<?php namespace Mcmraak\Rivercrs\Classes;

use DB;
use Mcmraak\Rivercrs\Models\Motorships;
use Input;
use View;

class RivercrsShips
{
    public static function getFormData()
    {
        $decks_count = DB::table('mcmraak_rivercrs_techs_pivot as pivot')
            ->where('pivot.tech_id', 9)
            ->groupBy('value')
            ->select('pivot.value as name')
            ->get();

        $decks_count_options = [];
        foreach ($decks_count as $item) {
            $decks_count_options[] = [
                'id' => $item->name,
                'name' => $item->name
            ];
        }

        $statuses = DB::table('mcmraak_rivercrs_ship_statuses')->get();
        $statuses_options = [];
        foreach ($statuses as $item) {
            $statuses_options[] = [
                'id' => $item->id,
                'name' => $item->name
            ];
        }

        return [
            'ships' => [
                'value' => [],
                'options' => Motorships::cleanNames(),
            ],
            'desks_count' => [
                'value' => [],
                'options' => $decks_count_options
            ],
            'status' => [
                'value' => [],
                'options' => $statuses_options
            ]
        ];
    }

    # http://azimut.dc/rivercrs/api/searchships?dump
    public static function search() : array
    {
        $form = Input::get('form');
        $ships = $form['ships'] ?? null;
        $desks_counts = $form['desks_count'] ?? null;
        $ship_statuses = $form['status'] ?? null;

        $ships = DB::table('mcmraak_rivercrs_motorships as ship')
            ->where(function ($query) use ($ships, $desks_counts, $ship_statuses) {
                if ($ships) {
                    $query->whereIn('ship.id', $ships);
                }

                if ($desks_counts) {
                    $query->where('tech.tech_id', 9);
                    $query->where(function ($query) use ($desks_counts) {
                        foreach ($desks_counts as $desk_count) {
                            $query->orWhere('tech.value', $desk_count);
                        }
                    });
                }

                if ($ship_statuses) {
                    $query->where(function ($query) use ($ship_statuses) {
                        foreach ($ship_statuses as $ship_status) {
                            $query->orWhere('status.id', $ship_status);
                        }
                    });
                }
            });

        if ($desks_counts) {
            $ships->join('mcmraak_rivercrs_techs_pivot as tech', 'tech.motorship_id', '=', 'ship.id');
        }
        if ($ship_statuses) {
            $ships->join('mcmraak_rivercrs_ship_statuses as status', 'status.id', '=', 'ship.status_id');
        }

        $ships = $ships->select('ship.id as id')->get();
        $ships_ids = $ships->pluck('id')->toArray();
        self::checkForActualFilter($ships_ids);
        $ships = Motorships::whereIn('id', $ships_ids)->get();
        RivercrsSearch::addTemporaryDiscounts($ships, $ships_ids);

        $items = [];
        foreach ($ships as $ship) {
            $items[] = [
                'motorship_id' => $ship->id,
                'pic' => $ship->pic,
                'youtube_link' => $ship->youtube_link,
                'name' => $ship->alt_name,
                'motorship_status' => $ship->status->name ?? null,
                'motorship_status_desc' => $ship->status->desc ?? null,
                'permanent_discounts' => $ship->permanent_discounts,
                'techs' => $ship->techs_arr
            ];
        }

        RivercrsSearch::addTemporaryDiscounts($items);

        if (isset($_GET['dump'])) {
            dd($items);
        }

        return [
            'items' => $items,
            'count' => $ships->count()
        ];
    }

    # Функция фильтрует id теплоходов которые деактивированы или не имеют заездов
    private static function checkForActualFilter(&$ids)
    {
        if (!$ids) {
            return;
        }
        $checkins_pivot = DB::table('mcmraak_rivercrs_checkins as checkin')
            ->join('mcmraak_rivercrs_motorships as ship', 'ship.id', '=', 'checkin.motorship_id')
            ->select('ship.id as id')
            ->groupBy('id')
            ->pluck('id')
            ->toArray();
        $ids = array_intersect($ids, $checkins_pivot);
    }
}
