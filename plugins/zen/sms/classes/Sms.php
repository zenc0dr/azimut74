<?php namespace Zen\Sms\Classes;

use Zen\Sms\Models\Profile;
use Zen\Sms\Models\Item;
use Queue;
use Log;

class Sms
{
    public function fire($job, $data)
    {
        $this->sendSMS($data);
        $job->delete();
    }

    public static function send($phone, $text, $profile_code)
    {
//        Queue::push('Zen\Sms\Classes\Sms', [
//            'phone' => $phone,
//            'text' => $text,
//            'profile_code' => $profile_code
//        ]);
        $sms = new self;
        $sms->sendSMS([
            'phone' => $phone,
            'text' => $text,
            'profile_code' => $profile_code
        ]);
    }

    function sendSMS($data)
    {
        if (env('APP_ENV') === 'dev') {
            \Log::debug([
                'sms_send' => $data
            ]);
            return;
        }

        $phone = $data['phone'];
        $text = $data['text'];
        $profile_code = $data['profile_code'];
        $profile = Profile::where('code', $profile_code)->first();
        if(!$profile) return;
        $api_key = $profile->key;
        $from = ($profile->from)?"&from={$profile->from}":'';
        $sms = new Item;
        $sms->profile_id = $profile->id;
        $sms->phone = $phone;
        $sms->text = $text;
        $text = urlencode($text);
        $balance = file_get_contents("http://sms.ru/sms/send?api_id=$api_key&to=$phone&text=$text$from");
        $sms->balance = $balance;
        $sms->save();
    }

}
