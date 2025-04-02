<?php

namespace Zen\Forms\Classes\Api;

use Zen\Forms\Models\Item;

class Sender
{
    # http://azimut.dc/zen/forms/api/Sender:send
    public function send()
    {
        $data = [
            'name' => request('name'),
            'phone' => request('phone'),
            'info' => request('info')
        ];

        $filled = !empty($data['name']) && !empty($data['phone']) && !empty($data['info']);

        if (!$filled) {
            return forms()->response([
                'alert' => 'Форма не отправлена, не все данные заполнены'
            ]);
        }

        Item::create([
            'code' => 'simple-phone',
            'data' => forms()->toJson($data, true),
            'status' => 'filled'
        ]);

        # Дополнительно посылаем данные в AMO
        \Http::post('https://tglk.ru/in/5EyxAHdgnHQyzmB5', function ($http) use ($data) {
            $http->data($data);
        });

        return forms()->response([
            'alert' => 'Данные отправлены'
        ]);
    }
}
