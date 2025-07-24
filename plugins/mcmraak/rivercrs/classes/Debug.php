<?php namespace Mcmraak\Rivercrs\Classes;

use Mcmraak\Rivercrs\Classes\ExistTest;
use Mcmraak\Rivercrs\Console\Check;
use Mcmraak\Rivercrs\Models\Booking;
use Mcmraak\Rivercrs\Models\Motorships;
use Mcmraak\Rivercrs\Models\Checkins;
use Mcmraak\Rivercrs\Classes\ExistTurbo;
use Input;
use Zen\Captcha\Classes\Captcha;
use Carbon\Carbon;

use Zen\Uongate\Models\Settings;
use Mcmraak\Rivercrs\Models\Motorships as Ship;

class Debug
{
    # http://azimut.dc/rivercrs/debug/Debug@test
    public function test()
    {


    }


    # http://azimut.dc/rivercrs/debug/Debug@generateCheckinsVector
    public function generateCheckinsVector()
    {
        $checkins = Checkins::where('eds_code', 'waterway')->get();

        # https://xn----7sbveuzmbgd.xn--p1ai/russia-river-cruises/cruise/{id}

        $lines = [];
        $ships = [];
        foreach ($checkins as $checkin) {

            $ships[$checkin->motorship->id] = $checkin->motorship->name;

            $line = [
                'checkin_id' => $checkin->id,
                'date_from' => $checkin->date,
                'date_to' => $checkin->dateb,
                'ship_id' => $checkin->motorship->id,
                //'source' => $checkin->eds_name,
            ];

            $lines[] = join("|", $line);
        }

        $output = [
            'Формирование ссылки на круиз' => 'https://xn----7sbveuzmbgd.xn--p1ai/russia-river-cruises/cruise/{id} (id первый столбец в списке cruises)',
            'ships' => $ships,
            'cruises' => $lines,
        ];

        file_put_contents(
            storage_path('temp/checkins.json'),
            json_encode($output, JSON_PRETTY_PRINT)
        );
    }

    # http://azimut.dc/rivercrs/debug/Debug@exTest?id=5009
    public function exTest()
    {
        $checkin_id = Input::get('id');
        $checkin = Checkins::find($checkin_id);

        if (!$checkin) {
            die('No checkin');
        }

        $extTest = new ExistTest();

        $extTest->get($checkin);

        # http://azimut.dc/rivercrs/debug/Debug@exTest
        //app('Mcmraak\Rivercrs\Classes\ExistTest')->get($checkin, 'array');
    }

    # http://azimut.dc/rivercrs/debug/Debug@exTestTurbo?id=5009
    public function exTestTurbo()
    {
        $checkin_id = Input::get('id');
        $checkin = Checkins::find($checkin_id);

        if (!$checkin) {
            die('No checkin');
        }

        $extTest = new ExistTurbo();
        $extTest->request($checkin);
    }
}
