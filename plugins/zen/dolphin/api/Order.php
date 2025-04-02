<?php namespace Zen\Dolphin\Api;

use Zen\Dolphin\Classes\Core;
use Zen\Dolphin\Models\Order as OrderModel;
use Zen\Dolphin\Models\Tour;
use Zen\Uongate\Classes\Lead;

class Order extends Core
{
    # http://azimut.dc/zen/dolphin/api/order:send
    public function send()
    {
        $order = $this->input('order');

        //file_put_contents(base_path('order.log'), serialize($order));
        //$order = unserialize(file_get_contents(base_path('order.log')));

        //dd($order);

        $scope = $order['scope'] ?? null; #-------------- Тип виджета ext || atm
        $tour_id = $order['tour_id'] ?? null; #---------- Идентификатор тура
        $tour_name = $order['tour_name'] ?? null; #------ Название тура
        $hotel_id = $order['hotel_id'] ?? null; #-------- Идентификатор отеля
        $hotel_name = $order['hotel_name'] ?? null; #---- Имя отеля
        $tarrif_id = $order['tarrif_id'] ?? null; #------ Идентификатор тарифа
        $tarrif_name = $order['tarrif_name'] ?? null; #-- Имя тарифа
        $name = trim($order['name'] ?? '');     #---------- Имя клиента
        $phone = $order['phone'] ?? null; #-------------- Телефон клиента
        $email = trim($order['email'] ?? ''); #-------- email клиента
        $desc = trim($order['desc'] ?? ''); #---------- Дополнительные данные
        $date_of = $order['date_of'] ?? null; #---------- Дата начала тура
        $date_to = $order['date_to'] ?? null; #---------- Дата окончания тура
        $nights = $order['nights'] ?? null; #------------ Количество ночей
        $days = $order['days'] ?? null; #---------------- Количество дней
        $pansion = $order['pansion'] ?? null; #---------- Питание
        $room = $order['room'] ?? null; #---------------- Тип номера ex: 1-место || 13 мест. (разм. в 14 мест) ...
        $roomc = $order['roomc'] ?? null; #-------------- Категория номера ex: коттедж (фин) || полулюкс ...
        $adults = $order['adults'] ?? null; #------------ Количество взрослых
        $children_ages = $order['childrens'] ?? null; #-- Возрасты детей
        $price = $order['price'] ?? null; #-------------- Цена
        $refer = $order['refer'] ?? null; #-------------- Ссылка с которой была отправлена заявка

        if ($scope === 'atp') {
            $children_ages = $order['children_ages'] ?? [];
        }

        # Предварительная обработка
        $phone = preg_replace('/\D+/', '', $phone);

        $validator = $this->validator();

        $input_data = [
            'name|Имя|min:3|max:255|required' => $name,
            'phone|Телефон|min:11|max:11|required' => $phone,
            'email|email|email|min:3|max:300' => $email,
            'desc|Дополнительная_информация|max:2000' => $desc
        ];

        $validator->validate($input_data);

        $alerts = $validator->alerts();

        # Если найдены ошибки
        if ($alerts) {
            $this->json([
                'success' => false,
                'alerts' => $alerts
            ]);
            return;
        }


        if (!$tour_name) {
            $tour_name = Tour::find($tour_id)->name;
        }

        # Создание завки
        $order = new OrderModel;

        $note = "Название формы: Форма бронирования;\n" .
            "Имя: $name;\n" .
            "Телефон: $phone;\n" .
            "email: $email;\n" .
            "Дополнительная информация: $desc;\n" .
            "Адрес страницы: $refer\n;";
        $abs_days = $days ?? $nights + 1;
        $note .= "Тур: $tour_name;\n";
        if ($hotel_name) {
            $note .= "Отель: $hotel_name;\n";
        }
        $note .= "Период: $date_of - $date_to;\n";
        $note .= "Питание: $pansion;\n";
        $note .= "Дней: $abs_days;\n";
        $note .= "Взрослых: $adults;\n";

        if ($children_ages) {
            $note .= "Детей:" . count($children_ages) . ";\n";
            $note .= "Возраст детей:" . join(', ', $children_ages) . ";\n";
        }

        $note .= "Цена: $price руб.;";

        if ($scope === 'ext') {
            $order->addExtOrder([
                'scope' => $scope,
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'desc' => $desc,
                'tour_name' => $tour_name,
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

            Lead::push([
                'source' => 'ext',
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'note' => $note,
                'Amo.Integration' => [
                    'name' => $name,
                    'phone' => $phone,
                    'email' => $email,
                    'page_url' => $refer,
                    'desc' => $desc,
                    'tour_name' => $tour_name,
                    'hotel_name' => $hotel_name,
                    'nights' => $nights,
                    'meal' => $pansion,
                    'adults' => $adults,
                    'kids' => count($children_ages),
                    'kids_ages' => $children_ages,
                    'price' => $price,
                    'date_of' => $date_of,
                    'date_to' => $date_to,
                ]
            ]);

        }

        if ($scope === 'atp') {
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

            Lead::push([
                'source' => 'atm',
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'note' => $note,
                'Amo.Integration' => [
                    'name' => $name,
                    'phone' => $phone,
                    'email' => $email,
                    'page_url' => $refer,
                    'desc' => $desc,
                    'tour_name' => $tour_name,
                    'hotel_name' => $hotel_name,
                    'nights' => $nights,
                    'meal' => $pansion,
                    'adults' => $adults,
                    'kids' => count($children_ages),
                    'kids_ages' => $children_ages,
                    'price' => $price,
                    'date_of' => $date_of,
                    'date_to' => $date_to,
                ]
            ]);
        }

        $order_subject = 'Уведомление о бронировании';
        if ($scope == 'ext') {
            $order_subject = 'Заявка на экскурс. тур';
        }
        if ($scope == 'atm') {
            $order_subject = 'Заявка на автобусный тур';
        }

        # Уведомление
        $this->notice()->sendMail([
            'subject' => $order_subject,
            'html' => $order->getHtml(),
        ]);

        # Заявка принята
        $this->json([
            'success' => true,
            'alerts' => [
                [
                    'text' => "Заявка на бронирование №$order->id принята!",
                    'type' => 'success'
                ]
            ],
        ]);
    }
}
