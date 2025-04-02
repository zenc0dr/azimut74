<?php namespace Zen\Dolphin\Api;

use Zen\Dolphin\Classes\Core;
use Zen\Dolphin\Models\Tour;
use Zen\Dolphin\Models\Tarrif;
use Zen\Dolphin\Models\Hotel;
use Zen\Dolphin\Models\Azroom;
use Zen\Dolphin\Models\Pricetype;

use Log;
use DB;


class Tarrifs extends Core
{
    # http://azimut.dc/zen/dolphin/api/tarrifs:records?tour_id=1
    function records()
    {
        $tour_id = $this->input('tour_id');
        if(!$tour_id) die('error 1');
        $tour = Tour::find($tour_id);
        if(!$tour) die('error 2');
        $tarrifs = $tour->tarrifs;

        $output = [];
        foreach ($tarrifs as $tarrif) {

            $hotel = @$tarrif->hotel;

            $output[] = [
                'id' => $tarrif->id,
                'name' => $tarrif->name,
                'hotel' => ($hotel) ? "[id:{$hotel->id}] {$hotel->name}":null,
                'number' => $tarrif->number_name,
                'comfort' => @$tarrif->azcomfort->name,
                'pansion' => @$tarrif->azpansion->name,
                'operator' => @$tarrif->operator->name,
            ];
        }
        $this->json($output);
    }

    // Create || Open
    # http://azimut.dc/zen/dolphin/api/tarrifs:open?tarrif_id=null // Создать новый тариф
    # http://azimut.dc/zen/dolphin/api/tarrifs:open?tarrif_id=19
    function open()
    {
        $tarrif_id = $this->input('tarrif_id');
        $tarrif = ($tarrif_id) ? Tarrif::find($tarrif_id) : new Tarrif;

        $operator = $this->vueSelect('operator', $tarrif->operator_id);
        $pansion = $this->vueSelect('azpansion', $tarrif->azpansion_id);
        $hotel = $this->vueSelect('hotel', $tarrif->hotel_id, $this->getHotelOptions($tarrif->hotel_id));
        $comfort = $this->vueSelect('azcomfort', $tarrif->azcomfort_id);
        $reduct = $this->vueSelect('reduct', $tarrif->reduct_id);

        $prices = $tarrif->prices;

        $data = [
            'name' => $tarrif->name ?? 'Новый тариф',
            'number_name' => $tarrif->number_name ?? '',
            'operator' => $operator,
            'pansion' => $pansion,
            'hotel' => $hotel,
            'comfort' => $comfort,
            'dates' => ($prices) ? array_keys($prices) : [],
            'azrooms' => Azroom::lists('name', 'id'),
            'pricetypes' => Pricetype::lists('name', 'id'),
            'prices' => (object) $prices,
            'reduct' => $reduct,
            'reduct_dates' => $tarrif->reduct_dates
        ];

        $this->json($data);
    }

    # http://azimut.dc/zen/dolphin/api/tarrifs:save
    function save()
    {
        $this->isAdmin();

        $tour_id = $this->input('tour_id');
        $tarrif_id = $this->input('tarrif_id');
        $data = $this->input('data');

//        $this->log([
//            'dump' => [
//                'tour_id' => $tour_id,
//                'tarrif_id' => $tarrif_id,
//                'data' => $data,
//            ]
//        ]);


        ##############   DEBUG   #########################


        /*
        $cabox = new Cabox('dolphin.service');
        $cache_data = $cabox->get('tarrif.data');
        if($cache_data) {
            $tour_id = $cache_data['tour_id'];
            $tarrif_id = $cache_data['tarrif_id'];
            $data = $cache_data['data'];
        } else {
            $cabox->put('tarrif.data', [
                'tour_id' => $tour_id,
                'tarrif_id' => $tarrif_id,
                'data' => $data
            ]);
        }

        #DB::table('zen_dolphin_tarrifs')->truncate();
        #DB::table('zen_dolphin_prices')->truncate();

        */

        ###################################################


        if(!$this->validateInputData($data)) {
            $this->json([
                'alert' => [
                    'text' => 'Ошибка формата в одной из ячеек "возраст"',
                    'type' => 'danger'
                ]
            ]);
            return;
        }

        $tarrif = ($tarrif_id) ? Tarrif::find($tarrif_id) : new Tarrif;
        if(!$tarrif->tour_id) $tarrif->tour_id = $tour_id;
        $tarrif->name = @$data['name'];
        $tarrif->number_name = @$data['number_name'];
        $tarrif->hotel_id = $data['hotel_id'];
        $tarrif->operator_id = $data['operator_id'];
        $tarrif->azpansion_id = $data['pansion_id'];
        $tarrif->azcomfort_id = $data['comfort_id'];
        $tarrif->prices = $data['prices'];
        $tarrif->reduct_id = @$data['reduct_id'];
        $tarrif->reduct_dates = @$data['reduct_dates'];
        $tarrif->save();


        $conflict = $this->reductsConflict($tarrif, @$data['reduct_dates']);

        if($conflict) {
            $this->json([
                'tarrif_id' => $tarrif->id,
                'alert' => [
                    'text' => $conflict,
                    'type' => 'danger'
                ]
            ]);
        } else {
            $this->json([
                'tarrif_id' => $tarrif->id,
                'alert' => [
                    'text' => 'Сохранено',
                    'type' => 'success'
                ]
            ]);
        }


    }

    private function reductsConflict($tarrif)
    {
        return $tarrif->getConflict();
    }

    private function validateInputData($data)
    {
        foreach ($data['prices'] as $date) {
            foreach ($date as $price) {
                $ages = $price['ages'];
                if(!$ages) continue;
                if(!preg_match('/^\d+-\d+$/', $ages)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function getHotelOptions($hotel_id)
    {
        if(!$hotel_id) return [];
        $hotel = Hotel::find($hotel_id);
        return [
            [
                'id' => $hotel->id,
                'name' => "{$hotel->id}_{$hotel->created_by}: {$hotel->name}"
            ]
        ];
    }

    # Функция для формирования опций для гостиницы
    # http://azimut.dc/zen/dolphin/api/tarrifs:hotels
    function hotels()
    {
        $search_text = $this->input('serch_text');
        $hotels = Hotel::where('name', 'like', "%$search_text%")->orderBy('name')->get();
        $return = [];
        foreach ($hotels as $hotel) {
            $return[] = [
                'id' => $hotel->id,
                'name' => "{$hotel->id}_{$hotel->created_by}: {$hotel->name}"
            ];
        }
        $this->json($return);
    }

    # http://azimut.dc/zen/dolphin/api/tarrifs:deleteTarrif
    function deleteTarrif()
    {
        $this->isAdmin();
        $tarrif_id = $this->input('tarrif_id');
        $tarrif = Tarrif::find($tarrif_id);
        if(!$tarrif) return;
        $tarrif->delete();
        $this->json([
            'alert' => [
                'text' => 'Тариф удалён',
                'type' => 'success'
            ]
        ]);
    }

    # http://azimut.dc/zen/dolphin/api/tarrifs:deleteTarrifs
    function deleteTarrifs()
    {
        $this->isAdmin();
        $tarrif_ids = $this->input('tarrif_ids');
        DB::table('zen_dolphin_tarrifs')->whereIn('id', $tarrif_ids)->delete();
        DB::table('zen_dolphin_prices')->whereIn('tarrif_id', $tarrif_ids)->delete();
        $this->json([
            'alert' => [
                'text' => 'Тарифы удалёны',
                'type' => 'success'
            ]
        ]);
    }

}
