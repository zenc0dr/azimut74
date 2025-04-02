<?php

namespace Zen\Master\Api;

class Log
{
    # azimut.s/master.api.Log.addYandexId?yandex_id=888888888888888888
    public function addYandexId()
    {
        $yandex_id = request('yandex_id');
        master()->log(
            'Фиксация Yandex_ID перед оправкой формы',
            [
                'yandex_id' => $yandex_id
            ]
        );
    }
}
