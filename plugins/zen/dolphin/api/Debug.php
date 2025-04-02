<?php namespace Zen\Dolphin\Api;

use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Zen\Dolphin\Classes\Core;
use DB;
use Zen\Dolphin\Models\Hotel;
use Zen\Dolphin\Models\Tour;
use View;
use Zen\Putpics\Models\Task;
use Zen\Greeter\Controllers\Showcases;
use Zen\Dolphin\Models\Order;

class Debug extends Core
{
    public function __construct()
    {
        $this->isAdmin();
    }

    # http://azimut.dc/zen/dolphin/api/debug:extSearch
    public function extSearch()
    {
        $search_query = [
            'geo_objects' => [],
            'date_of' => '28.04.2022',
            'date_to' => '28.05.2022',
            'date' => null,
            'tour_id' => null,
            'days' => [],
            'adults' => 1,
            'childrens' => [],
            'list_type' => 'schedule'
        ];

        $offer_query = [
            'geo_objects' => [],
            'date' => '09.07.2022',
            'tour_id' => 246,
            'days' => [6, 7, 8, 9, 10, 11, 12, 13, 14],
            'adults' => 1,
            'childrens' => [3, 5],
            'list_type' => 'offers'
        ];


        $output = $this->store('ExtSearch')->query($search_query);

        dd($output);
    }

    # http://azimut.dc/zen/dolphin/api/debug:debugGreeter
    public function debugGreeter()
    {
        Showcases::createCache();
    }

    # http://azimut.dc/zen/dolphin/api/debug:debugImport
    public function debugImport()
    {
        app('Zen\Dolphin\Console\Import')->handle();
    }

    # http://azimut.dc/zen/dolphin/api/debug:test
    public function test()
    {
        deprecator('callme')->catch([
            'phone' => 9173237700,
            'email' => 'zen@8ber.ru'
        ]);

        dump('Заявка отправлена');

        deprecator()->save();
    }
}
