<?php

namespace Mcmraak\Rivercrs\Services;

use DB;
use Cache;

class RiverSitemap
{
    public static function generate()
    {
        if (Cache::has('rivercrs.sitemap.items')) {
            return Cache::get('rivercrs.sitemap.items');
        }

        $items = [];

        $url_start = env('APP_URL') . '/russia-river-cruises/';

        DB::table('mcmraak_rivercrs_cruises')
            ->where('active', 1)
            ->select([
                'slug'
            ])
            ->get()
            ->map(function ($item) use (&$items, $url_start) {
                $items[] = [
                    'url' => $url_start . $item->slug,
                    'title' => null,
                ];
            });

        DB::table('mcmraak_rivercrs_transit')
            ->where('active', 1)
            ->select([
                'slug'
            ])
            ->get()
            ->map(function ($item) use (&$items, $url_start) {
                $items[] = [
                    'url' => $url_start . $item->slug,
                    'title' => null,
                ];
            });

        DB::table('mcmraak_rivercrs_motorships')
            ->join(
                'mcmraak_rivercrs_checkins',
                'mcmraak_rivercrs_checkins.motorship_id',
                '=',
                'mcmraak_rivercrs_motorships.id'
            )
            ->whereNotNull('mcmraak_rivercrs_checkins.motorship_id')
            ->select([
                'mcmraak_rivercrs_motorships.id'
            ])
            ->orderBy('mcmraak_rivercrs_motorships.id')
            ->distinct()
            ->get()
            ->map(function ($item) use (&$items, $url_start) {
                $items[] = [
                    'url' => $url_start . 'motorship/' . $item->id,
                    'title' => null,
                ];
            });

        DB::table('mcmraak_rivercrs_checkins')
            ->where('active', 1)
            ->select([
                'id'
            ])
            ->get()
            ->map(function ($item) use (&$items, $url_start) {
                $items[] = [
                    'url' => $url_start . 'cruise/'. $item->id,
                    'title' => null,
                ];
            });

        Cache::add('rivercrs.sitemap.items', $items, 1439);
        return $items;
    }
}
