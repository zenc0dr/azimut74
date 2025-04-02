<?php namespace Zen\GroupTours\Store;

use Zen\Dolphin\Classes\Core as DolphinCore;
use Zen\GroupTours\Models\Tour;

class TourPage extends Store
{
    public function get(array $data): ?array
    {
        $tour_id = $data['tour_id'];
        $tour = Tour::active()->find($tour_id);

        if (!$tour) {
            return null;
        }

        $page_groups = (new DolphinCore)->store('PageGroups')->getFromScope('ext');

        return [
            'type' => 'tour',
            'name' => $tour->name . ", {$tour->days} дн.",
            'meta_title' => $tour->meta_title ?? $tour->name,
            'meta_description' => $tour->meta_description,
            'pageGroups' => $page_groups,
            'preview' => $tour->preview_image,
            'gallery' => $tour->gallery, # Галерея
            'days' => $tour->days,
            'waybill' => $tour->waybill_array,
            'price' => $tour->price,
            'important_info' => $tour->important_info,
            'announcement' => $tour->announcement,
            'conditions' => $tour->conditions_array, # Условия тура
            'tour_program' => $tour->tour_program, # Программа тура
            'youtube_link' => $tour->youtube_link,
            'tags' => collect($tour->tags_array)->pluck('name')->toArray(),
            'order' => $this->json([
                'id' => $tour->id,
                'name' => $tour->name,
                'days' => $tour->days,
                'price' => $tour->price,
            ], true),
            'files' => $this->store('FileBlock')
        ];
    }
}
