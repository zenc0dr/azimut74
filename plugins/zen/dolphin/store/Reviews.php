<?php

namespace Zen\Dolphin\Store;

use Zen\Dolphin\Models\Review;
use Zen\Grinder\Classes\Grinder;

class Reviews
{
    public function get()
    {
        return Review::orderBy('sort_order')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->name,
                    'date' => $item->date_time->format('d.m.Y'),
                    'time' => $item->date_time->format('H:i'),
                    'link' => $item->source_link,
                    'stars' => $item->stars,
                    'text' => $item->text,
                    'avatar' => Grinder::open($item->avatar)->width('200')->getThumb(),
                    'image' => Grinder::open($item->image)->width('800')->getThumb(),
                ];
            })->toJson(256);
    }
}
