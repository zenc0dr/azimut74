<?php namespace Mcmraak\Rivercrs\Classes;

use Mcmraak\Rivercrs\Models\Cabins;
use Input;
use View;

class RivercrsCabin
{
    public static array $place_names = [
        1 => 'Одно',
        2 => 'Двух',
        3 => 'Трёх',
        4 => 'Четырёх',
        5 => 'Пяти',
        6 => 'Шести',
        7 => 'Семи',
        8 => 'Восьми',
        9 => 'Девяти',
        10 => 'Десяти',
    ];

    public static function getCabinInfo(): string
    {
        $cabin_id = Input::get('cabin_id');
        $cabin = Cabins::find($cabin_id);
        return View::make('mcmraak.rivercrs::rivercrs_cabin_modal', [
            'cabin' => $cabin,
            'placenames' => self::$place_names,
            'room_data' => null
        ])->render();
    }

    public static function openCabin(): string
    {
        $data = Input::all();

        $category_id = intval($data['c']);
        $room_number = $data['n'];
        $free_status = $data['f'];
        $deck_id = $data['d'];
        $check = $data['check'];

        $cabin = Cabins::find($category_id);

        $room_data = [
            'room_number' => $room_number,
            'free_status' => $free_status,
            'check' => $check,
            'deck_id' => $deck_id,
        ];

        return View::make('mcmraak.rivercrs::rivercrs_cabin_modal', [
            'cabin' => $cabin,
            'placenames' => self::$place_names,
            'room_data' => $room_data
        ])->render();
    }
}
