<?php namespace Mcmraak\Rivercrs\Models;

use Model;
use DB;
use Mcmraak\Rivercrs\Classes\Exist;
use Log;
use View;
use Carbon\Carbon;

/**
 * Model
 */
class Booking extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /*
     * Validation
     */
    public $rules = [
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'mcmraak_rivercrs_booking';

    public $belongsTo = [
        'checkin' => [
            'Mcmraak\Rivercrs\Models\Checkins',
            'key' => 'checkin_id'
        ],
    ];

    public function cabins()
    {
        $checkin_id = $this->checkin_id;

        $exist = new Exist;
        $exist_data = $exist->get($checkin_id, 'array');

        $return = [];

        foreach ($this->cabins as $selected_cabin) {
            foreach ($exist_data['decks'] as $deck) {
                if ($deck['id'] == $selected_cabin['deck_id']) {
                    foreach ($deck['cabins'] as $cabin) {
                        if ($cabin['id'] == $selected_cabin['cabin_id']) {
                            $return[] = [
                                'num' => $selected_cabin['num'],
                                'cabin_name' => $cabin['name'],
                                'deck_name' => $deck['name'],
                                'prices' => $cabin['prices'],
                            ];
                        }
                    }
                }
            }
        }

        if (!$return) {
            $return = $this->cabinsPatch($exist_data);
        }

        return $return;
    }

    private function cabinsPatch(array $exist_data): array
    {
        $return = [];

        foreach ($this->cabins as $selected_cabin) {
            foreach ($exist_data['decks'] as $deck) {
                foreach ($deck['cabins'] as $cabin) {
                    if ($cabin['id'] == $selected_cabin['cabin_id']) {
                        $return[] = [
                            'num' => $selected_cabin['num'],
                            'cabin_name' => $cabin['name'],
                            'deck_name' => $deck['name'] . ' (Не точно)',
                            'prices' => $cabin['prices'],
                        ];
                    }
                }
            }
        }
        return $return;
    }

    public function getCabinsAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setCabinsAttribute($value)
    {
        $this->attributes['cabins'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public function createOrder($json = true)
    {
        $order = [
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'peoples' => $this->peoples,
            'date' => Carbon::parse($this->created_at)->format('d.m.Y H:i'),
            'cabins' => $this->cabins()
        ];

        if ($json) {
            return json_encode($order, JSON_UNESCAPED_UNICODE);
        }
        return $order;
    }

    public function beforeSave()
    {
        $this->attributes['order'] = $this->createOrder();
    }

    public function textOrder()
    {
        $order_data = $this->createOrder(false);
        $text_data = "";

        $checkin = Checkins::find($this->checkin_id);
        if ($checkin) {
            $ship_name = $checkin->motorship->name;
            $waybill = $checkin->getWaybillLine();
            $text_data .= "Теплоход: $ship_name\n";
            $text_data .= "Маршрут: $waybill\n";
        }

        $text_data .= "Количество человек: {$order_data['peoples']}\n";
        foreach ($order_data['cabins'] as $cabin) {
            $text_data .= "Номер: {$cabin['num']}, Каюта: {$cabin['cabin_name']}, Палуба: {$cabin['deck_name']}\n";
            foreach ($cabin['prices'] as $price) {
                $text_data .= " -- Цена:: мест:{$price['price_places']}, цена1:{$price['price_value']}, "
                    . "цена2:{$price['price2_value']};";
            }
        }

        return $text_data;
    }

}
