<?php namespace Zen\Dolphin\Store;

use Zen\Dolphin\Classes\Core;
use Zen\Dolphin\Models\Hotel;
use DB;

class Hotels extends Core
{
    public function byAzimutTour($tour_id)
    {
        # Получить id отелей
        $hotel_ids = DB::table('zen_dolphin_tours as tour')
            ->where('tour.id', $tour_id)
            ->join(
                'zen_dolphin_tarrifs as tarrif',
                'tarrif.tour_id',
                '=',
                'tour.id'
            )
            ->join(
                'zen_dolphin_hotels as hotel',
                'hotel.id',
                '=',
                'tarrif.hotel_id'
            )
            ->select(
                'hotel.id as hotel_id'
            )
            ->pluck('hotel_id')->toArray();

        $hotels = Hotel::whereIn('id', $hotel_ids)->get();
        return $this->hotelsToArray($hotels);
    }

    public function byDolphinTour($tour)
    {
        $hotel_eids = @$tour['Hotels'];
        if (!$hotel_eids) {
            return;
        }

        $hotels = Hotel::whereIn('eid', $hotel_eids)->get();

        return $this->hotelsToArray($hotels);
    }

    private function hotelsToArray($hotels)
    {
        $output = [];

        foreach ($hotels as $hotel) {
            if ($hotel->id == 2164) {
                continue;
            }
            $output[] = $hotel->data;
        }

        return $output;
    }
}
