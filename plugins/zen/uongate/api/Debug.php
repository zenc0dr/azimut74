<?php namespace Zen\Uongate\Api;

use Zen\Uongate\Classes\Lead;
use Cache;
use Session;

class Debug extends Api
{
    # http://azimut.dc/zen/uongate/api/debug:testApi
    public function testApi()
    {
        $time = now()->format('d.m.Y H:i:s');

        file_put_contents(
            storage_path('logs/amo_queries.log'),
            "$time: Отправлены тестовые данные" . PHP_EOL,
            FILE_APPEND
        );
    }

    # http://azimut.dc/zen/uongate/api/debug:testQuery
    public function testQuery()
    {
        Lead::push([
            'source' => 'ext',
            'name' => 'Тест Тестович Тестовский',
            'phone' => null,
            'email' => 'нет',
            'note' => 'Дополнительная информация'
        ]);
    }

    # http://azimut.dc/zen/uongate/api/debug:testUtm
    public function testUtm()
    {
        dd(
            \Cache::get('utm_' . \Session::getId())
        );
    }
}
