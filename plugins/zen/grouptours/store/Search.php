<?php namespace Zen\GroupTours\Store;

use DB;
use Zen\Dolphin\Models\City;
use Zen\GroupTours\Models\Tour;

class Search extends Store
{
    private array $geo_objects;
    private array $tags;
    private array $days;

    public function get(array $data)
    {
        $this->geo_objects = $data['geo_objects'];
        $this->tags = $data['tags'];
        $this->days = $data['days'];

        return $this->search();
    }

    private function search()
    {
        $geo_childrens = $this->addGeoChildrens();
        $fat = DB::table('zen_grouptours_tours as tour');

        # Поиск по Гео-объектам
        if ($this->geo_objects) {
            $fat->where(function ($query) use ($geo_childrens) {

                # Без учёта точек отправления и прибытия
                $geo_objects = $this->geo_objects;
                foreach ($geo_objects as $geo_object) {
                    $query->orWhere('tour.waybill', 'like', "% $geo_object %");
                }

                # Дочерние гео-объекты
                if ($geo_childrens) {
                    foreach ($geo_childrens as $geo_object) {
                        $query->orWhere('tour.waybill', 'like', "% $geo_object %");
                    }
                }
            });
        }

        # Поиск по тегам
        if ($this->tags) {
            # Подключение сводной таблицы тегов
            $fat->join('zen_grouptours_tours_tags_pivot as pivot', 'pivot.tour_id', '=', 'tour.id');

            $fat->where(function ($query) {
                foreach ($this->tags as $tag_id) {
                    $query->orWhere('pivot.tag_id', $tag_id);
                }
            });
        }

        # Поиск по дням
        if ($this->days) {
            $fat->where(function ($query) {
                $days = $this->days;
                foreach ($days as $day) {
                    $query->orWhere('tour.days', $day);
                }
            });
        }

        $fat->select([
            'tour.id as tour_id'
        ]);

        $tour_ids = $fat->pluck('tour_id')->unique()->toArray();
        $tours = Tour::whereIn('id', $tour_ids)
            ->active()
            ->orderBy('days')
            ->orderBy('price')
            ->orderBy('name')
            ->get();

        return $this->render($tours);
    }

    private function addGeoChildrens()
    {
        if (!$this->geo_objects) {
            return null;
        }

        $geo_objects = collect($this->geo_objects)->filter(function ($item) {
            # Отбрасываем метки мест
            if (strpos($item, 'ps') !== false) {
                return false;
            }

            # Отбрасываем метки НЕ регионов
            if (strpos($item, '1:') === false) {
                return false;
            }

            return true;
        })->toArray();

        # Получить гео.объекты, родителями которых являются эти
        # regions - Тут только регионы

        $regions_ids = collect($geo_objects)->map(function ($item) {
            return explode(':', $item)[1];
        })->toArray();

        $city_geocodes = City::whereIn('region_id', $regions_ids)
            ->where('pertain_id', 0)
            ->get()
            ->map(function ($item) {
                return "2:{$item->id}";
            })->toArray();

        if (!$city_geocodes) {
            return null;
        }

        return $city_geocodes;
    }

    private function render($tours): array
    {
        $output = [];
        foreach ($tours as $tour) {
            $output[] = [
                'id' => $tour->id,
                'tour_name' => $tour->name,
                'snippet' => $tour->snippet,
                'days' => $tour->days,
                'waybill' => $tour->waybill_array,
                'labels' => $tour->tags_array,
                'price' => $tour->price,
                'qq' => $this->qqBuild($tour)
            ];
        }
        return $output;
    }

    private function qqBuild($tour)
    {
        $azimut_href = 'https://xn----7sbveuzmbgd.xn--p1ai/group-tours/shkolnye-tury-v-saratove';

        $output = [
            'thumbnail' => env('APP_URL') . $tour->snippet,
            'checkinDt' => now()->format('d.m.Y'),
            'country' => 'Россия',
            'hotelName' => $tour->name . ", {$tour->days} дн.",
            'region' => join(' - ', $tour->waybill_array),
            'nights' => $tour->days - 1,
            'price' => $tour->price,
            'currency' => 'RUB',
            'operator' => 'Азимут-Тур',
            'excursion' => true,
            'boardType' => 'По программе тура',
            'occupancy' => [
                'adultsCount' => 1,
                'childrenCount' => 0,
                'childAges' => '',
            ],
            'city_from' => '',
            'roomType' => '',
            'comment' => "- Даты тура: возможна любая дата, по желанию заказчика\n- Цена рассчитана при количестве группы 40+4. При другой численности цена может отличаться\n- Нажмите на название тура - откроется программа тура, описание услуг, входящих в стоимость",
            'href' => env('APP_URL') . '/group-tours/tour-' . $tour->id
        ];

        return $output;
    }
}
