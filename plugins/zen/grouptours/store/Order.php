<?php namespace Zen\GroupTours\Store;

use Zen\GroupTours\Models\Order as OrderModel;
use Mail;
use View;
use Zen\Uongate\Classes\Lead;
use Zen\GroupTours\Models\Tour;


class Order extends Store
{
    public function get(array $data): array
    {
        $name = $data['name'];
        $email = $data['email'];
        $phone = $data['phone'];
        $desc = $data['desc'];
        $price = $data['price'];
        $tour_id = $data['tour_id'];
        $tour_name = $data['tour_name'];

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
            return [
                'success' => false,
                'alerts' => $alerts
            ];
        }


        $order = new OrderModel();
        $order->data = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'desc' => $desc,
            'tour_id' => $tour_id,
            'tour_name' => $tour_name,
            'price' => $price,
        ];

        $order->save();

        # Список email для уведомлений из настроек
        $emails = collect($this->setting('emails'))->map(function ($item) {
            return $item['email'];
        })->toArray();

        $subject = $this->setting('message');
        $subject = str_replace('#id', $tour_id, $subject);
        $subject = str_replace('#name', $name, $subject);

        $send_data = $order->data;
        $send_data['url'] = env('APP_URL') . '/console/zen/grouptours/orders/update/' . $order->id;
        $send_data['time'] = $order->created_at->format('d.m.Y H:i');

        $raw = [
            'html' => View::make('zen.grouptours::order', $send_data)->render(),
            'text' => null,
        ];

        Mail::raw($raw, function ($message) use ($subject, $emails) {
            $message->subject($subject);
            $message->to($emails);
        });

        $tour = Tour::find($tour_id);
        $waybill = join(' - ', $tour->waybill_array);

        $note = "Название формы: Групповые туры;\n" .
            "Имя: $name;\n" .
            "Телефон: $phone;\n" .
            "email: $email;\n" .
            "Маршрут: $waybill;\n" .
            "Цена: $tour->price;\n" .
            "Дополнительно: $desc";

        Lead::push([
            'source' => 'grouptours',
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'note' => $note,
            'Amo.Integration' => [
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'page_url' => request()->url(),
                'desc' => $desc,
                'waybill' => $waybill,
                'price' => $tour->price
            ]
        ]);

        # Заявка принята
        return [
            'success' => true,
            'emails' => $emails,
            'alerts' => [
                [
                    'text' => "Заявка на бронирование №$order->id принята!",
                    'type' => 'success'
                ]
            ],
        ];
    }
}
