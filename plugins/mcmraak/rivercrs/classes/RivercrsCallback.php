<?php namespace Mcmraak\Rivercrs\Classes;

use Session;
use Input;
use Mail;
use DB;
use Zen\Uongate\Classes\Lead;
use Cache;

class RivercrsCallback
{
    public static function send()
    {
        //return; #TODO(zenc0rd): Временно закрыто
        $session_key = 'rivercrs_callback_timestamp';
        $next_allowed_time = intval(Session::get($session_key));
        $repeatTime = 2; # В секундах

        if ($next_allowed_time && time() < $next_allowed_time) {
            $timeDiff = $next_allowed_time - time();

            # Защита от перебора
            return [
                'success' => false,
                'alerts' => [
                    [
                        'text' => "Возможно повторить через $timeDiff сек",
                        'type' => 'dunger',
                        'field' => 'notime'
                    ]
                ],
            ];
        }

        $token = request('token');
        if (!Cache::has("$token.callback.token")) {
            abort(403);
        }

        # Время повторной отправки в секундах
        $name = trim(Input::get('name'));
        //$email = trim(Input::get('email'));
        $email = 'нет';
        $note = $parameters = trim(Input::get('note'));
        $phone = master()->strings()->handlePhone(request('phone'));
        $refer = trim(Input::get('refer'));


        # https://8ber.kaiten.ru/45922735 (мой телефон для тестов)
        if (in_array($phone, master()->blackList())) {
            master()->telegram()->sendMessage("Заблокированный человек с номером $phone пытался отправить заявку на звонок, но был игнорирован.");
            return [
                'success' => true,
                'message' => "Заказ на звонок отправлен",
                'alerts' => [],
            ];
        }

        deprecator('callme')->catch([
            $phone
        ]);

        $validator = new ValidatorHelper;

        $input_data = [
            'name|Имя|min:3|max:255|required' => $name,
            'phone|Телефон|min:11|max:11|required' => $phone,
            //'email|email|email|min:6|max:300' => $email,
            'note|Параметры_тура|min:5|max:300' => $note,
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

        DB::table('srw_azimut_callme')->insert([
            'name' => $name,
            'phone' => $phone,
            'email' => null,
            'message' => 'Заказ сделан: ' . date('d.m.Y H:i'). ' Параметры тура: '. $note,
            'ide' => 'Виджет',
            'tour' => 'Не определён',
            'ip' => request()->ip()
        ]);

        deprecator()->save();

        $subject = 'Заказан звонок';
        $to = 'azimut-kruiz@yandex.ru';
        $raw = [
            'html' => "Имя: $name, Телефон: $phone, email: $email, Параметры тура: $note",
            'text' => "Имя: $name, Телефон: $phone, email: $email, Параметры тура: $note",
        ];

        $source_code = 'callme';

        if (strpos($refer, '/russia-river-cruises') !== false) {
            $source_code = 'callme-rivercrs';
        }

        $note = "Название формы: Форма обратной связи;\n" .
            "Имя: $name;\n" .
            "Телефон: $phone;\n" .
            "email: $email;\n" .
            "Дополнительная информация: $note;\n" .
            "Адрес страницы: $refer;";

        Lead::push([
            'source' => $source_code,
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'note' => $note,
            'Amo.Integration' => [
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'page_url' => $refer,
                'desc' => $parameters,
                'yandex_id' => request('yandex_id'),
                'utm' => \Cache::get('utm_' . \Session::getId())
            ]
        ]);

        Mail::raw($raw, function ($message) use ($subject, $to) {
            $message->subject($subject);
            $message->to($to);
        });

        Session::put($session_key, time() + $repeatTime);
        # Заявка принята
        return [
            'success' => true,
            'message' => "Заказ на звонок отправлен",
            'alerts' => [],
        ];
    }
}
