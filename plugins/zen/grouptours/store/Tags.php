<?php

namespace Zen\GroupTours\Store;
use DB;

class Tags extends Store
{
    public function get($data)
    {
        DB::unprepared("SET sql_mode = ''");
        $tags = DB::table('zen_grouptours_tours as tour')
            ->where('tour.active', 1)
            ->where('tag.active', 1)
            ->join(
                'zen_grouptours_tours_tags_pivot as pivot',
                'pivot.tour_id', '=', 'tour.id'
            )
            ->join(
                'zen_grouptours_tags as tag',
                'tag.id', '=', 'pivot.tag_id'
            )
            ->groupBy('tag.id')
            ->orderBy('tag.sort_order')
            ->select([
                'tag.id as id',
                'tag.name as name'
            ])
            ->get()
            ->map(function ($item) {
                return (array) $item;
            })->toArray();

        return $tags;
    }
}
