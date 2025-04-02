<?php namespace Zen\Dolphin\Models;

use Model;
use View;

/**
 * Model
 */
class Order extends Model
{
    use \October\Rain\Database\Traits\Validation;

    public $table = 'zen_dolphin_orders';
    public $rules = [];

    public function setDataAttribute($value)
    {
        $this->attributes['data'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public function getDataAttribute($value)
    {
        return json_decode($value, true);
    }

    public function addExtOrder($data)
    {
        $this->data = [
            'scope' => $data['scope'], # Тип виджета
            'name' => $data['name'], # Имя клиента
            'phone' => $data['phone'], # Телефон
            'email' => $data['email'], # email
            'desc' => $data['desc'], # Дополнительная информация о туристах
            'tour_name' => $data['tour_name'], # Название тура
            'date_of' => $data['date_of'], # Дата С
            'date_to' => $data['date_to'], # Дата ПО
            'nights' => $data['nights'], # Ночей
            'pansion' => $data['pansion'], # Питание
            'room' => $data['room'], # Тип номера
            'roomc' => $data['roomc'], # Категория номера
            'adults' => $data['adults'], # Количество взрослых
            'children_ages' => $data['children_ages'], # Возрасты детей
            'price' => $data['price'], # Цена
        ];

        $this->save();
    }

    public function addAtmOrder($data)
    {
        $this->data = [
            'scope' => $data['scope'], # Тип виджета
            'name' => $data['name'], # Имя клиента
            'phone' => $data['phone'], # Телефон
            'email' => $data['email'], # email
            'desc' => $data['desc'], # Дополнительная информация о туристах
            'tour_id' => $data['tour_id'], # id тура
            'tour_name' => $data['tour_name'], # Название тура
            'tarrif_id' => $data['tarrif_id'], # id тарифа
            'tarrif_name' => $data['tarrif_name'], # Название тарифа
            'hotel_id' => $data['tarrif_id'], # id отеля
            'hotel_name' => $data['hotel_name'], # Название отеля
            'date_of' => $data['date_of'], # Дата С
            'date_to' => $data['date_to'], # Дата ПО
            'days' => $data['days'], # Дней
            'adults' => $data['adults'], # Количество взрослых
            'children_ages' => $data['children_ages'], # Возрасты детей
            'price' => $data['price'], # Цена
        ];

        $this->save();
    }

    public function getNameAttribute()
    {
        return $this->dataDump['name'] ?? null;
    }

    public function getPhoneAttribute()
    {
        return $this->dataDump['phone'] ?? null;
    }

    public function getEmailAttribute()
    {
        return $this->dataDump['email'] ?? null;
    }

    public function getScopeAttribute()
    {
        if (!isset($this->dataDump['scope'])) {
            return 'Нет';
        }

        $scope = $this->dataDump['scope'];

        return $scope == 'atp' ? 'Атм' : 'Экс';
    }

    public function getHtml()
    {
        return View::make('zen.dolphin::backend.order_inline', ['order' => $this])->render();
    }


    ### Events
    public $dataDump;

    public function afterFetch()
    {
        $this->dataDump = $this->data;
    }
}
