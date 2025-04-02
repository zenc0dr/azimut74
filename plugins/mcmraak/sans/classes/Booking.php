<?php namespace Mcmraak\Sans\Classes;

use Input;
use Validator;
use Log;
use View;
use Mcmraak\Sans\Controllers\Parser;
use Mcmraak\Sans\Controllers\Pages;
use Mcmraak\Sans\Models\Booking as B;
use Session;
use Mcmraak\Sans\Models\Settings;
use Mail;
use Zen\Sms\Classes\Sms;
use Zen\Uongate\Classes\Lead;

class Booking
{
    public $input;
    public $registred_actions = [
        'send',
        'getModal'
    ];

    public function json($array){
        echo json_encode ($array, JSON_UNESCAPED_UNICODE);
    }

    public function testAction($action){
        if(in_array($action,$this->registred_actions)){
            return true;
        }
    }

    public static function api($action)
    {
        $sync = new self;
        if($sync->testAction($action)){
            $sync->input = Input::all();
            $sync->{$action}();
        };
    }

    public static function validation($data)
    {
        $return = [
            'messages' => [],
            'badfields' => [],
        ];
        $make_a = [];
        $make_b = [];
        foreach ($data as $key => $val)
        {
            $names = explode('|', $key);
            $make_a[$names[1]] = $val[0];
            $make_b[$names[1]] = $val[1];
        }

        $validator = Validator::make($make_a, $make_b);

        if ($validator->fails()){
            $messages = $validator->messages();
            foreach ($data as $key => $val){
                $names = explode('|', $key);
                if ($messages->has($names[1])) {
                    $return['messages'][] = $messages->first($names[1]);
                    $return['badfields'][$names[0]] = true;
                }
            }
        }
        return $return;
    }

    public function getModal()
    {
        $data = $this->dataMiner();

        $tour_date = $data['tour_date'];
        $arrDate = explode('.', $tour_date);
        $rus_months = [
            'Января',
            'Февраля',
            'Марта',
            'Апреля',
            'Мая',
            'Июня',
            'Июля',
            'Августа',
            'Сентября',
            'Октября',
            'Ноября',
            'Декабря',
            ];
        $d = $arrDate[0];
        $m = $arrDate[1];
        $y = $arrDate[2];
        $day = 86400;
        $date_of = mktime(0, 0, 0, $m, $d, $y);
        $date_of_string = $d . ' ' .$rus_months[intval($m)-1];
        $date_to = $date_of + ($day * intval($data['nights']));
        $date_to_m = intval(date('m', $date_to));
        $date_to_string = date('d', $date_to) . ' ' .$rus_months[$date_to_m-1];
        $data['date_of'] = $date_of_string;
        $data['date_to'] = $date_to_string;
        $sans_query_data = Session::get('sans_query_data');
        $data['adults'] = $sans_query_data['adults'];
        $data['kids'] = $sans_query_data['kids'];
        $data['kidsAges'] = $sans_query_data['kidsAges'];
        Session::put('sans_booking_data', $data);

        echo View::make('mcmraak.sans::booking', ['data' => $data]);
    }

    public function dataMiner(){
        $booking_key = $this->input['booking_key'];
        $keys = explode('::', $booking_key);
        $query_key = $keys[0];
        $result_key = $keys[1];
        $cache_file = base_path().'/storage/sans_cache/queries/'.$query_key.'.gz';
        $results = Parser::getCache($cache_file);
        foreach ($results as $result){
            $hash = Pages::resultToHash($result);
            if($result_key == $hash) return $result;
        }
    }

    public function send()
    {
        $data = (object) Session::get('sans_booking_data');
        $name = trim($this->input['name']);
        $phone = preg_replace('/\D/','', $this->input['phone']);
        $email = trim($this->input['email']);
        $desc = trim($this->input['desc']);
        $refer = trim($this->input['refer']);

        //print_r($date);

        /* Validation */
        $return = $this->validation([
            'name|Имя' => [$name, 'required|min:3|max:300'],
            'phone|Телефон' => [$phone, 'required|min:11|max:11'],
            'email|Электронная_почта' => [$email, 'required|email|min:3|max:300'],
            'desc|Дополнительная_информация' => [$desc, 'max:2000']
        ]);

        if($return['messages']){
            $return['success'] = false;
            $this->json($return);
            return;
        }

        $note = "Название формы: Форма бронирования;\n" .
            "Имя: $name;\n" .
            "Телефон: $name;\n" .
            "email: $email;\n" .
            "Дополнительная информация: $desc;\n" .
            "Адрес страницы: $refer;\n";

        $note .= "Тур: $data->tour_name;\n";
        $note .= "Отель: $data->hotel_name;\n";
        $note .= "Страна: $data->сountry_name;\n";
        $note .= "Город: $data->town_name;\n";
        $note .= "Номер: $data->room_type;\n";
        $note .= "Ночей: $data->nights;\n";
        $note .= "Питание: $data->meal;\n";
        $note .= "Взрослых: $data->adults;\n";
        $note .= "Детей: $data->kids;\n";
        $note .= "Возраст детей: $data->kidsAges;\n";
        $note .= "Цена: $data->price руб.;";

        Lead::push([
            'source' => 'sans',
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
                'tour_name' => $data->tour_name,
                'hotel_name' => $data->hotel_name,
                'country_name' => $data->сountry_name,
                'room_type' => $data->room_type,
                'nights' => $data->nights,
                'meal' => $data->meal,
                'adults' => $data->adults,
                'kids' => $data->kids,
                'kids_ages' => $data->kidsAges,
                'price' => $data->price,
            ]
        ]);

        $booking_item = new B;
        $booking_item->data = [
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'desc' => $desc,
            'hotel_id' => $data->hotel_id,
            'tour_name' => $data->tour_name,
            'tour_date' => $data->tour_date,
            'hotel_type' => $data->hotel_type,
            'hotel_name' => $data->hotel_name,
            'town_name' => $data->town_name,
            'сountry_name' => $data->сountry_name,
            'room_type' => $data->room_type,
            'nights' => $data->nights,
            'meal' => $data->meal,
            'price' => $data->price,
            'address' => $data->address,
            'adults' => $data->adults,
            'kids' => $data->kids,
            'kidsAges' => $data->kidsAges,
        ];
        $booking_item->save();

        $return['messages']  = ['Заявка отправлена. Менеджер свяжется с Вами в ближайшее время!'];
        $return['success'] = true;
        $return['badfields'] = [];
        $this->json($return);

        $adminEmails = Settings::get('booking_emails');

        if($adminEmails) {

            $emailsToSend = [];
            foreach ($adminEmails as $item) {
                $emailsToSend[] = $item['email'];
            }

            Mail::send([
                'text' => '',
                'html' => View::make('mcmraak.sans::mail.booking', [
                    'item' => $booking_item,
                ]),
                'raw' => true
            ], [null], function ($message) use ($emailsToSend, $booking_item) {
                //$message->subject('Бронь ч/з Алеан №'.$booking_item->id); // Заявка по Алеану
                $message->subject('Заявка по Алеану');
                $message->to($emailsToSend, 'Администратор сайта');
            });

        }

        if(Settings::get('sms')){
            $sms_text = Settings::get('sms_text');
            $sms_text = str_replace('$id', $booking_item->id, $sms_text);
            Sms::send($phone, $sms_text, Settings::get('sms_prifile'));
        }

    }
}
