<?php namespace Zen\Dolphin\Api;

use Zen\Dolphin\Classes\Core;
use Zen\Dolphin\Models\Order as OrderModel;
use Zen\Dolphin\Models\Tour;

class OrderOld extends Core
{
    # http://azimut.dc/zen/dolphin/api/order:send
    function send()
    {
        $order = $this->input('order');

        $scope = @$order['scope']; #-------------- Тип виджета exp || atp
        $source_code = @$order['source_code']; #-- Код истоничка a || d
        $tour_id = @$order['tour_id']; #---------- Идентификатор тура
        $tour_name = @$order['tour_name']; #------ Название тура
        $hotel_id = @$order['hotel_id']; #-------- Идентификатор отеля
        $hotel_name = @$order['hotel_name']; #---- Имя отеля
        $tarrif_id = @$order['tarrif_id']; #------ Идентификатор тарифа
        $tarrif_name = @$order['tarrif_name']; #-- Имя тарифа
        $name = trim(@$order['name']); #---------- Имя клиента
        $phone = @$order['phone']; #-------------- Телефон клиента
        $email = trim(@$order['email']); #-------- email клиента
        $desc = trim(@$order['desc']); #---------- Дополнительные данные
        $date_of = @$order['date_of']; #---------- Дата начала тура
        $date_to = @$order['date_to']; #---------- Дата окончания тура
        $nights = @$order['nights']; #------------ Количество ночей
        $days = @$order['days']; #---------------- Количество дней
        $pansion = @$order['pansion']; #---------- Питание
        $room = @$order['room']; #---------------- Тип номера ex: 1-место || 10-мест || 13 мест. (разм. в 14 мест) ...
        $roomc = @$order['roomc']; #-------------- Категория номера ex: коттедж (фин) || полулюкс ...
        $adults = @$order['adults']; #------------ Количество взрослых
        $children_ages = @$order['childrens']; #-- Возрасты детей
        $price = @$order['price']; #-------------- Цена

        # Предварительная обработка
        $phone = preg_replace('/\D+/', '', $phone);

        $validator = $this->validator();

        $input_data = [
            'name|Имя|min:3|max:255|required' => $name,
            'phone|Телефон|min:11|max:11|required' => $phone,
            'email|email|email|min:3|max:300' => (string) $email,
            'desc|Дополнительная_информация|max:2000' => $desc
        ];

        $validator->validate($input_data);

        $alerts = $validator->alerts();

        # Если найдены ошибки
        if($alerts) {
            $this->json([
                'success' => false,
                'alerts' => $alerts
            ]);
            return;
        }

        # Создание завки
        $order = new OrderModel;

        if($scope == 'exp') {
            $order->addExtOrder([
                'scope' => $scope,
                'source' => $source_code,
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'desc' => $desc,
                'tour_name' => $this->getTourName($tour_id, $source_code),
                'date_of' => $date_of,
                'date_to' => $date_to,
                'nights' => $nights,
                'pansion' => $pansion,
                'room' => $room,
                'roomc' => $roomc,
                'adults' => $adults,
                'children_ages' => $children_ages,
                'price' => $price,
            ]);
        }

        if($scope == 'atp') {
            $order->addAtmOrder([
                'scope' => $scope,
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'desc' => $desc,
                'tour_id' => $tour_id,
                'tour_name' => $tour_name,
                'tarrif_id' => $tarrif_id,
                'tarrif_name' => $tarrif_name,
                'hotel_id' => $hotel_id,
                'hotel_name' => $hotel_name,
                'date_of' => $date_of,
                'date_to' => $date_to,
                'days' => $days,
                'adults' => $adults,
                'children_ages' => $children_ages,
                'price' => $price,
            ]);
        }

        $order_subject = 'Уведомление о бронировании';
        if($scope == 'exp' && $source_code == 'a') $order_subject = 'Заявка на экскурс.тур (Азимут)';
        if($scope == 'exp' && $source_code == 'd') $order_subject = 'Заявка на экскурс.тур (Дельфин)';
        if($scope == 'atp') $order_subject = 'Заявка на автобусный тур';

        # Уведомление
        $this->notice()->sendMail([
            # 'subject' => 'Уведомление о бронировании',
            'subject' => $order_subject,
            'html' => $order->getHtml(),
        ]);

        # Заявка принята
        $this->json([
            'success' => true,
            'alerts' => [
                [
                    'text' => "Заявка на бронирование №{$order->id} принята!",
                    'type' => 'success'
                ]
            ],
        ]);
    }

    private function getTourName($tour_id, $source_code)
    {
        if($source_code == 'a') {
            return Tour::find($tour_id)->name;
        }

        if($source_code == 'd') {
            return $this->store('Dolphin')->getTour($tour_id)['Name'];
        }
    }
}
