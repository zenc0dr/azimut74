<?php namespace Mcmraak\Rivercrs\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Carbon\Carbon;
use Mcmraak\Rivercrs\Classes\Api;
use Mcmraak\Rivercrs\Models\Checkins;
use Mcmraak\Rivercrs\Models\Booking as Bookingmodel;
use Mcmraak\Rivercrs\Models\Settings;
use Mcmraak\Rivercrs\Settings\NotifySettings;
use Mail;
use Zen\Sms\Classes\Sms;
use Zen\Uongate\Classes\Lead;
use Cache;

class Booking extends Controller
{
    public $implement = [
        'Backend\Behaviors\ListController',
        'Backend\Behaviors\FormController',
        'Backend\Behaviors\ReorderController'
    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $reorderConfig = 'config_reorder.yaml';

    public $requiredPermissions = [
        'mcmraak.rivercrs.motorships'
    ];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Mcmraak.Rivercrs', 'rivercrs', 'rivercrs-booking');
    }

    public function sendBooking($prok = false)
    {
        $api = new Api;
        $checkin_id = $api->input('checkin_id', 'trim');
        $name = $api->input('name', 'trim');
        $phone = $api->input('phone', 'num');
        $email = $api->input('email', 'trim');
        $desc = $api->input('desc', 'trim');
        $cabins = (isset($api->input->cabins)) ? $api->input->cabins : false;
        $peoples = $api->input('peoples', 'trim');
        $refer = $api->input('refer', 'trim');

        $phone = master()->strings()->handlePhone($phone);

        /* Validation */
        $return = $api->validate([
            'name|Имя' => [$name, 'required|min:3|max:300'],
            'phone|Телефон' => [$phone, 'required|min:11|max:11'],
            'email|Электронная_почта' => [$email, 'email|min:3|max:300'],
            'desc|Описание' => [$desc, 'min:3|max:300'],
        ]);

        if (!$cabins) {
            $return['alerts'][] = [
                'field' => '',
                'type' => 'danger',
                'text' => 'Выберите хотя бы одну каюту'
            ];
        }

        # Валидация НЕ прошла
        if ($return['alerts'] != []) {
            $api->json($return);
            return;
        }

        deprecator('RiverCRS.booking')->catch([
            $phone,
            $checkin_id,
            $cabins
        ]);

        # Валидация прошла
        $booking = new Bookingmodel;
        $booking->checkin_id = $checkin_id;
        $booking->cabins = $cabins;
        $booking->name = $name;
        $booking->email = $email;
        $booking->phone = $phone;
        $booking->peoples = $peoples;
        $booking->desc = $desc;
        $booking->save();

        deprecator()->save();

        #Send Email
        $settings = Settings::find(1);
        $emails = $settings->bookingemails;

        $emailsToSend = [];
        foreach ($emails as $admin_email) {
            $emailsToSend[] = $admin_email['bemail'];
        }

        $to = 'Администраторам';

        $subj = NotifySettings::get($prok ? 'pk_subject' : 'rc_subject');
        $subj = str_replace('$id', $booking->id, $subj);

        Mail::send([
            'text' => "",
            'html' => \View::make('mcmraak.rivercrs::booking_report', ['booking' => $booking]),
            'raw' => true
        ], [null], function ($message) use ($emailsToSend, $to, $subj) {
            $message->to($emailsToSend, $to);
            $message->subject($subj);
        });

        $note = "Название формы: Форма бронирования;\n" .
            "Имя: $name;\n" .
            "Телефон: $phone;\n" .
            "email: $email;\n" .
            "Дополнительная информация о туристах: $desc;\n" .
            "Адрес страницы: $refer;\n";

        $note .= $booking->textOrder();

        $checkin = Checkins::find($checkin_id);
        $order_data = $booking->createOrder(false);
        $price = 0;
        foreach ($order_data['cabins'] as $cabin) {
            foreach ($cabin['prices'] as $cabin_price) {
                $price += $cabin_price['price_value'];
            }
        }

        $price_ranges = \Zen\Uongate\Models\Settings::get('price_ranges');
        $price_level = 0;
        foreach ($price_ranges as $price_range) {
            $price_level++;
            $of = $price_range['of'];
            $to = $price_range['to'];
            if ($price >= $of && $price <= $to) {
                break;
            }
        }

        $waybill_arr = $checkin->getWaybillCleanArr() ?? [];
        $first_town = $waybill_arr[0] ?? null;

        $utm = \Cache::get('utm_' . \Session::getId());
        $yclid = $utm['yclid'] ?? null;

        $notify_text = NotifySettings::get($prok ? 'prokruiz_sms' : 'rivercrs_sms');
        $notify_text = str_replace('$id', $booking->id, $notify_text);

        # https://8ber.kaiten.ru/space/45609/card/43215635
        if ($yclid == '16420801915972485119') {
            $return['alerts'][] = [
                'field' => '',
                'type' => 'success',
                'text' => $notify_text
            ];
            $return['success'] = true;
            $api->json($return);
            return;
        }

        # https://8ber.kaiten.ru/45922735
        if (in_array($phone, master()->blackList())) {
            master()->telegram()->sendMessage("Заблокированный человек с номером $phone пытался забронировать рейс, но был игнорирован.");
            $return['alerts'][] = [
                'field' => '',
                'type' => 'success',
                'text' => $notify_text
            ];
            $return['success'] = true;
            $api->json($return);
            return;
        }

        Lead::push([
            'source' => $prok ? 'prok' : 'rivercrs',
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
                'ship_name' => $checkin->motorship->standard_name,
                'date_of' => Carbon::parse($checkin->date)->format('d.m.Y'),
                'date_to' => Carbon::parse($checkin->date_b)->format('d.m.Y'),
                'time_of' => Carbon::parse($checkin->date)->format('H:i'),
                'time_to' => Carbon::parse($checkin->date_b)->format('H:i'),
                'waybill' => join(' - ', $waybill_arr),
                'order' => $order_data,
                'town' => $first_town,
                'price_level' => $price_level,
                'yandex_id' => request('yandex_id'),
                'utm' => $utm
            ],
            'Calltouch.Integration' => [
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
            ]
        ]);

        if (NotifySettings::get('sms')) {
            Sms::send($phone, $notify_text, NotifySettings::get('sms_profile'));
        }

        $return['alerts'][] = [
            'field' => '',
            'type' => 'success',
            'text' => $notify_text
        ];

        $return['success'] = true;
        $api->json($return);
    }
}
