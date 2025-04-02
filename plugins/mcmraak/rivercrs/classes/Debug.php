<?php namespace Mcmraak\Rivercrs\Classes;

use Mcmraak\Rivercrs\Classes\ExistTest;
use Mcmraak\Rivercrs\Console\Check;
use Mcmraak\Rivercrs\Models\Booking;
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
        dd(
            request()->only([
                'utm_source',
                'utm_medium',
                'utm_campaign',
                'utm_content',
                'utm_term',
                'yclid'
            ])
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
