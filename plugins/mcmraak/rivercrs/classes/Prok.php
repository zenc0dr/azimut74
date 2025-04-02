<?php namespace Mcmraak\Rivercrs\Classes;

use Mcmraak\Rivercrs\Classes\CacheSettings as Settings;
use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use Mcmraak\Rivercrs\Classes\ValidatorHelper;
use Mcmraak\Rivercrs\Classes\ScheduleBuilder;
use Mcmraak\Rivercrs\Models\ShipStatus;
use Mcmraak\Rivercrs\Models\Motorships;
use Mcmraak\Rivercrs\Models\Discount;
use Mcmraak\Rivercrs\Models\Kpage;
use Mcmraak\Rivercrs\Models\Ktype;
use Zen\Grinder\Classes\Grinder;
use Cms\Classes\Theme;
use Carbon\Carbon;
use Session;
use Config;
use Input;
use Cache;
use View;
use Mail;
use DB;

use Mcmraak\Rivercrs\Classes\Exist;
use Zen\Cabox\Classes\Cabox;
use Mcmraak\Rivercrs\Models\Cabins as Cabin;

class Prok
{
    function json($array)
    {
        echo json_encode ($array, JSON_UNESCAPED_UNICODE);
    }

    private $_404;
    private function return404()
    {
        return '$this->setStatusCode(404);return $this->controller->run("404");';
    }

    private function trow404()
    {
        $this->_404 = true;
    }

    function is404()
    {
        if($this->_404) return $this->return404();
        return false;
    }

    # Формирование данных в шаблон
    static function addPages(&$page)
    {
        $types = Ktype::get();

        $self = new self;

        $menu = [];
        $footer_menu = [];
        foreach ($types as $type) {

            $items = [];
            $footer_items = [];
            $pages = $type->pages()->where('active', 1)->get();
            foreach ($pages as $kpage) {

                $url = ($kpage->ktype_id < 3) ? '/cruises/' . $kpage->slug : '/page/' . $kpage->slug;

                if($kpage->in_menu) {
                    $items[] = [
                        'name' => $kpage->name,
                        'menu_name' => $kpage->menu_name,
                        'url' => $url,
                        'image' => $kpage->pic
                    ];
                }

                if($kpage->in_footer) {
                    $footer_items[] = [
                        'name' => $kpage->name,
                        'menu_name' => $kpage->menu_name,
                        'url' => $url,
                        'image' => $kpage->pic
                    ];
                }

            }

            $menu[$type->id] = [
                'name' => $type->name,
                'items' => $items,
            ];

            $footer_menu[$type->id] = [
                'name' => $type->name,
                'items' => $footer_items,
            ];
        }

        $page['prok_items'] = $menu;
        $page['footer_menu'] = $footer_menu;
        $page['ships_menu'] = $self->getShipsMenu();
    }

    static function page(&$page)
    {
        $page->addCss('/themes/prokruiz/assets/css/blocks/kpage.css');
        $slug = $page->param('slug');
        $kpage = Kpage::where('slug', $slug)->first();
        $page['page_content'] = $kpage->data;
    }

    private function getShipsMenu()
    {
        return DB::table('mcmraak_rivercrs_motorships as ship')
            ->join('mcmraak_rivercrs_checkins as checkin', 'checkin.motorship_id', '=', 'ship.id')
            ->where('checkin.active', 1)
            ->select(
                'ship.id as id',
                'ship.name as name'
            )
            ->orderBy('ship.sort_order')
            ->groupBy('ship.id')
            ->take(7)
            ->get()
            ->toArray();
    }

    ### Поисковый виджет ###
    static function init(&$page)
    {
        $segments = request()->segments();

        if(!$segments || @$segments[0] == 'cruises') {
            $page->addCss('/plugins/mcmraak/rivercrs/assets/css/prok/multi_dropdown.css');
            $page->addCss('/plugins/mcmraak/rivercrs/assets/css/datepicker.min.css');
            $page->addJs('/plugins/mcmraak/rivercrs/assets/js/datepicker/datepicker.js');
            $page->addJs('/plugins/mcmraak/rivercrs/assets/js/vue.components.js');
            $page->addJs('/plugins/mcmraak/rivercrs/assets/js/pro-kruiz.widget.js');
        }

        if(!$segments) {
            $page->addJs('/plugins/mcmraak/rivercrs/assets/js/pro-kruiz.callback.js');
        }

        if(@$segments[0] == 'cruise') {
            $page->addCss('/plugins/mcmraak/rivercrs/assets/css/prok/cabin_modal.css');
            $page->addJs('/themes/prokruiz/assets/js/libs/jquery.inputmask.bundle.js');
            $page->addJs('/plugins/mcmraak/rivercrs/assets/js/pro-kruiz.booking.js');
        }

        if(@$segments[0] == 'ships') {
            $page->addCss('/plugins/mcmraak/rivercrs/assets/css/prok/multi_dropdown.css');
            $page->addJs('/plugins/mcmraak/rivercrs/assets/js/vue.components.js');
            $page->addJs('/plugins/mcmraak/rivercrs/assets/js/pro-kruize.ships.js');
        }

        if(@$segments[0] == 'ship') {
            $page->addCss('/plugins/mcmraak/rivercrs/assets/css/prok/multi_dropdown.css');
            $page->addCss('/plugins/mcmraak/rivercrs/assets/css/datepicker.min.css');
            $page->addJs('/plugins/mcmraak/rivercrs/assets/js/datepicker/datepicker.js');
            $page->addJs('/plugins/mcmraak/rivercrs/assets/js/vue.components.js');
            $page->addJs('/plugins/mcmraak/rivercrs/assets/js/pro-kruiz.widget.js');
        }

        $widget = new self;
        $page['prok'] = $widget;


        if($widget->is404()) return $widget->is404();
    }

    # http://azimut.dc/prok/api/callback?name=Alex&phone=79173237700
    function callback()
    {
        $session_key = 'prok_callback_timestamp';
        $next_allowed_time = intval(Session::get($session_key));
        $repiatTime = 30; # В секундах

        if($next_allowed_time && time() < $next_allowed_time) {
            $timeDiff = $next_allowed_time - time();

            # Защита от перебора
            $this->json([
                'success' => false,
                'alerts' => [
                    [
                        'text' => "Возможно повторить через $timeDiff сек",
                        'type' => 'dunger',
                        'field' => 'notime'
                    ]
                ],
            ]);
            return;
        }

        # Время повторной отправки в секундах
        $name = trim(Input::get('name'));
        $email = trim(Input::get('email'));
        $note = trim(Input::get('note'));
        $phone = trim(preg_replace("/\D/", "", trim(Input::get('phone'))));


        $validator = new ValidatorHelper;

        $input_data = [
            'name|Имя|min:3|max:255|required' => $name,
            'phone|Телефон|min:11|max:11|required' => $phone,
            'email|email|email|min:6|max:300' => $email,
            'note|Параметры_тура|min:5|max:300' => $note,
        ];

        $validator->validate($input_data);
        $alerts = $validator->alerts();
        # Если найдены ошибки
        if($alerts) {
            $this->json([
                'success' => false,
                'alerts' => $alerts
            ]);
            return;
        }

        DB::table('srw_azimut_callme')->insert([
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'message' => 'Заказ сделан: ' . date('d.m.Y H:i'). ' Параметры тура: '. $note,
            'ide' => 'Pro-kruiz.ru - Заказ звонка',
            'tour' => 'Не определён'
        ]);

        $subject = 'Заказан звонок';
        $to = 'azimut-kruiz@yandex.ru';
        $raw = [
            'html' => "Имя: $name, Телефон: $phone, email: $email, Параметры тура: $note",
            'text' => "Имя: $name, Телефон: $phone, email: $email, Параметры тура: $note",
        ];

        Mail::raw($raw, function($message) use ($subject, $to) {
            $message->subject($subject);
            $message->to($to);
        });

        Session::put($session_key, time() + $repiatTime);
        # Заявка принята
        $this->json([
            'success' => "Заказ на звонок отправлен",
            'alerts' => [],
        ]);
    }

    function callbackWidget()
    {
        return View::make('mcmraak.rivercrs::prok.callback');
    }

    # На старте
    function start()
    {
        return View::make('mcmraak.rivercrs::prok.search_widget', [
            'on_start' => true,
            'main_title' => @Theme::getActiveTheme()->main_title
        ])->render();
    }

    # На странице круизы
    function search()
    {
        $query = Input::get('search_query'); // {"d1":"12.05.2021","t1":[63],"t2":[61]}

        $kpage = null;
        $kpage_slug = @request()->segments()[1];
        if($kpage_slug) {
            $kpage = Kpage::where('slug', $kpage_slug)->first();
            if($kpage->query) $query = $kpage->query;
        }

        $widget = View::make('mcmraak.rivercrs::prok.search_widget', [
            'query_from_start' => $query,
            'title' => @$kpage->name,
            'bg_image' => @$kpage->image->path
        ])->render();

        return [
            'kpage' => $kpage,
            'widget' => $widget
        ];
    }

    # Страница круиз
    function cruise()
    {
        $checkin_id = intval(@request()->segments()[1]);

        $checkin = Checkin::find($checkin_id);

        if(!$checkin) return;


        $ship = $checkin->motorship;

        $data = [
            'checkin' => $checkin,
            'ship_id' => $ship->id,
            'ship_pic' => $ship->pic,
            'ship_name' => $ship->alt_name,
            'ship_images' => $ship->images->pluck('path')->toArray(),
            'ship_video' => $ship->youtube_link,
            'ship_desc' => $ship->desc,
            'ship_techs' => $ship->techs_arr,
            'ship_onboards' => $ship->onboards_arr,
            'ship_scheme' => $ship->ship_scheme,
            'ship_cabins' => $ship->decksWithCabins(),
            'ship_status' => @$ship->status->name,
            'permanent_discounts' => $ship->permanent_discounts,
            'temporary_discounts' => $ship->temporary_discounts,
            'json_data' => $checkin->json_data,
            'date' => $checkin->createDatesArray(),
            'waybill' => $checkin->getWaybill(' - '),
            'price_start' => $checkin->startPrice,
            'days' => $checkin->days . ' ' . $checkin->incline(['день', 'дня', 'дней'], $checkin->days),
            'price_contains' => $ship->add_a,
            'additionally_paid' => $ship->add_b,
            'statuses' => Settings::get('cabin_statuses'),
            'schedule' => $this->getSchedule($checkin),
            'checkin_prices' => json_encode(app('Mcmraak\Rivercrs\Classes\Exist')->get($checkin, 'array'), 256)
        ];

        return View::make('mcmraak.rivercrs::prok.cruise', $data)->render();
    }

    ### API ###
    # http://azimut.dc/prok/api/mounted
    function mounted()
    {
        echo (new \Mcmraak\Rivercrs\Classes\Search)->mounted();
    }

    # http://azimut.dc/prok/api/booking
    function booking()
    {
        (new \Mcmraak\Rivercrs\Controllers\Booking)->sendBooking(true);
    }

    # http://azimut.dc/prok/api/ksearch
    function ksearch()
    {
        $ids = Input::get('ids');
        #$ids = [191112]; # TODO:DEBUG

        if(!$ids) return;
        $checkins = Checkin::whereIn('id', $ids)
            ->where('active',1)
            ->orderBy('date')
            ->get();
        $output = [];

        foreach ($checkins as $checkin) {

            $ship = $checkin->motorship;

            $output[] = [
                'id' => $checkin->id,
                'image' => $ship->pic,
                'youtube' => $ship->youtube_link,
                'motorship_id' => $ship->id,
                'motorship_name' => $ship->alt_name,
                'motorship_desc' => $ship->desc,
                'motorship_status' => @$ship->status->name,
                'permanent_discounts' => $ship->permanent_discounts,
                'date' => $checkin->createDatesArray(),
                'waybill' => $checkin->getWaybill(' - '),
                'price_start' => $checkin->startPrice,
                'days' => $checkin->days . ' ' . $checkin->incline(['день', 'дня', 'дней'], $checkin->days),
            ];
        }

        #dd($output);

        $this->addTemporaryDiscounts($output);
        $html = View::make('mcmraak.rivercrs::prok.checkins', ['checkins' => $output])->render();
        $this->json([
            'html' => $html
        ]);
    }

    # Добавить временные скидки
    private function addTemporaryDiscounts(&$output = null, $ship_ids = null)
    {
        $ship_ids = $ship_ids ?? collect($output)->pluck('motorship_id')->unique()->toArray();

        $now_date = date('Y-m-d 00:00:00');
        $now_date_carbon = Carbon::parse($now_date);
        $discounts = DB::table('mcmraak_rivercrs_discounts')->where('valid_until', '>=', $now_date)
            ->where(function ($query) use ($ship_ids) {
                foreach ($ship_ids as $ship_id) {
                    $query->where('motorships', 'like', "%#$ship_id#%");
                }
            })->get();

        foreach ($output as &$item) {
            $item['temporary_discounts'] = [];
            foreach ($discounts as $discount) {
                if(strpos($discount->motorships, "#{$item['motorship_id']}#") !== false) {
                    $this->addTemporaryDiscount($item, $discount, $now_date_carbon);
                }
            }
        }
    }

    # Добавить временную скидку
    private function addTemporaryDiscount(&$item, $discount, $now_date)
    {
        $until = Carbon::parse($discount->valid_until);
        $before_until = $now_date->diffInDays($until, false);
        $title = ($before_until <= $discount->overlap_activation) ? $discount->overlap_title : $discount->title;
        $item['temporary_discounts'][] = [
            'image' => $discount->image,
            'title' => $title,
            'text' => $discount->desc,
        ];
    }

    # Получить расписание
    private function getSchedule($checkin)
    {
        $eds_code = $checkin->eds_code;


        if($eds_code == 'volga') {
            # TODO: Расписание Волги делается из XLS-файла, на данный момент его давно не обновляли
            //$data = \Mcmraak\Rivercrs\Controllers\VolgaSettings::getVolgaExcursion($checkin);
            return;
        }

        # http://azimut.dc/cruise/2449
        if($eds_code == 'germes') {
            $data = (new \Mcmraak\Rivercrs\Classes\Exist\GermesSchelude)->render($checkin, true);
            # arr: trace_table, excursion_table
            if(!@$data['trace_table']) return;
            return $this->germesSchedule($data['trace_table']);
        }

        # http://azimut.dc/cruise/1
        if($eds_code == 'waterway') {
            return $this->waterwaySchedule($checkin->desc_1);
        }

        # http://azimut.dc/cruise/1681
        if($eds_code == 'gama') {
            return $this->sheduleStandartTableToArray($checkin->desc_1);
        }

        return $this->sheduleStandartTableToArray($checkin->desc_1);
    }

    # Расписание водохода
    private function waterwaySchedule($table)
    {
        $table = str_replace('</tr>', '', $table);
        $table = str_replace('</td>', '', $table);

        $table_lines = explode("<tr>", $table);

        $schedule = new ScheduleBuilder;
        foreach ($table_lines as $table_line) {
            if (strpos($table_line, '<td>') !== 0) {
                continue;
            }

            if (strpos($table_line, '<td>День') === 0) {
                continue;
            }

            $table_line = explode('<td>', $table_line);

            preg_match('/^(\d+)[ |]<br>/m', @$table_line[1], $m);
            $schedule->day = @$m[1];
            preg_match('/<br>(\d+\.\d+\.\d+)<br>/', @$table_line[1], $m);
            $date = @$m[1];
            preg_match('/(\d+:\d+)/', @$table_line[1], $m);
            $time = @$m[1];
            preg_match('/\((\D+)\)/', @$table_line[1], $m);
            $camping = true;

            if (strpos(@$table_line[1], 'Отправление') !== false) {
                $camping = false;
                $schedule->date_depart = $date;
                $schedule->time_depart = $time;
            }
            if (strpos(@$table_line[1], 'Прибытие') !== false) {
                $camping = false;
                $schedule->date_arrive = $date;
                $schedule->time_arrive = $time;
            }
            # Стоянка
            if ($camping) {
                $schedule->date_arrive = $date;
                preg_match('/(\d{2}:\d{2}) - (\d{2}:\d{2})/', @$table_line[1], $m);
                $schedule->time_arrive = @$m[1];
                $schedule->time_depart = @$m[2];
            }
            $schedule->town = @$table_line[2];

            $desc = @$table_line[3];
            if ($desc) {
                $desc = preg_replace('/<\/{0,1}tbody>/', '', $desc);
                $desc = preg_replace('/<\/{0,1}table>/', '', $desc);
                $desc = preg_replace('/ {2,}/', ' ', $desc);
                $desc = trim($desc);
            }

            $schedule->desc = $desc;

            $schedule->addDay();

        }
        return $schedule->getSchedule();
    }

    # Расписание гермеса
    private function germesSchedule($trace_table)
    {
        $schedule = new ScheduleBuilder;
        $i=0;
        foreach ($trace_table as $day) {
            $i++;
            $schedule->day = $i;
            $schedule->date_arrive = $day['date_arrive'];
            $schedule->date_depart = $day['date_depart'];
            $schedule->time_arrive = $day['time_arrive'];
            $schedule->time_depart = $day['time_depart'];
            $schedule->town = $day['value'];
            $schedule->addDay();
        }
        return $schedule->getSchedule();
    }

    # Расписание стандартное
    private function sheduleStandartTableToArray($table) {

        $table = str_replace("\n", '', $table);
        $table = explode('<tr>', $table);

        $schedule = new ScheduleBuilder;
        $schedule->action = false;
        foreach ($table as $row) {
            $row = trim($row);
            if(strpos($row, '<td>') !== 0) continue;
            preg_match_all('/<td>([^<>]*)<\/td>/', $row, $m);
            $row = @$m[1];
            if(!$row) continue;

            $schedule->date_arrive = @$row[0];
            $schedule->town = @$row[1];
            $schedule->time_arrive = @$row[2];
            $schedule->time_diff = @$row[3];
            $schedule->time_depart = @$row[4];
            $schedule->addDay();
        }

        return $schedule->getSchedule();
    }

    function ships()
    {
        $form_json = Input::get('q');

        $ship_name = null;
        $desk_counts = null;
        $ship_statuses = null;

        $form = null;

        if($form_json) {
            $form = json_decode($form_json, true);
            if(@$form['n']) $ship_name = $form['n'];     #(str)
            if(@$form['d']) $desk_counts = $form['d'];   #(array)
            if(@$form['s']) $ship_statuses = $form['s']; #(array)
        }

        $ships = DB::table('mcmraak_rivercrs_motorships as ship')
            ->where(function($query) use ($ship_name, $desk_counts, $ship_statuses) {
                # Если задано имя
                if($ship_name) $query->where('ship.name', 'like', "%{$ship_name}%");

                # Если задано количество палуб
                if($desk_counts) {
                    $query->where('tech.tech_id', 9);
                    $query->where(function ($query) use ($desk_counts) {
                        foreach ($desk_counts as $desk_count) {
                            $query->orWhere('tech.value', $desk_count);
                        }
                    });
                }

                # Если задан статус
                if($ship_statuses) {
                    $query->where(function ($query) use ($ship_statuses) {
                        foreach ($ship_statuses as $ship_status) {
                            $query->orWhere('status.id', $ship_status);
                        }
                    });
                }
            });

        if($desk_counts) $ships->join('mcmraak_rivercrs_techs_pivot as tech', 'tech.motorship_id', '=', 'ship.id');
        if($ship_statuses) $ships->join('mcmraak_rivercrs_ship_statuses as status', 'status.id', '=', 'ship.status_id');

        $ships = $ships->select('ship.id as id')->get();
        $ships_ids = $ships->pluck('id')->toArray();
        $this->checkForActualFilter($ships_ids);
        $ships = Motorships::whereIn('id', $ships_ids)->get();
        $form_data = $this->makeFormData($ship_name, $desk_counts, $ship_statuses);
        $form_data = json_encode($form_data, JSON_UNESCAPED_UNICODE);
        $this->addTemporaryDiscounts($ships, $ships_ids);

        return View::make('mcmraak.rivercrs::prok.ships', [
            'form_data' => $form_data,
            'ships' => $ships,
        ])->render();
    }

    private function makeFormData($ship_name_def = null, $desk_count_def = null, $ship_statuse_def = null)
    {
        $decks_count = DB::table('mcmraak_rivercrs_techs_pivot as pivot')
            ->where('pivot.tech_id', 9)
            ->groupBy('value')
            ->select(
                'pivot.value as name'
            )->get();

        $decks_count_options = [];
        foreach ($decks_count as $item) {
            $decks_count_options[] = [
                'id' => $item->name,
                'name' => $item->name
            ];
        }

        $statuses = DB::table('mcmraak_rivercrs_ship_statuses')->get();
        $statuses_options = [];
        foreach ($statuses as $item) {
            $statuses_options[] = [
                'id' => $item->id,
                'name' => $item->name
            ];
        }

        return [
            'ship_name' => $ship_name_def,
            'desks_count' => [
                'value' => $desk_count_def ?? [],
                'options' => $decks_count_options
            ],
            'status' => [
                'value' => $ship_statuse_def ?? [],
                'options' => $statuses_options
            ]
        ];
    }

    # Функция фильтрует id теплоходов которые деактивированы или не имеют заездов
    private function checkForActualFilter(&$ids)
    {
        if(!$ids) return;
        $checkins_pivot = DB::table('mcmraak_rivercrs_checkins as checkin')
            ->join('mcmraak_rivercrs_motorships as ship', 'ship.id', '=', 'checkin.motorship_id')
            ->select('ship.id as id')
            ->groupBy('id')
            ->pluck('id')
            ->toArray();

        $ids = array_intersect($ids, $checkins_pivot);
    }

    # Страница /ship
    function ship()
    {
        $ship_id = @request()->segments()[1];

        $ship = Motorships::find($ship_id);

        $data = [
            'ship' => $ship,
            'ship_pic' => $ship->pic,
            'ship_name' => $ship->alt_name,
            'ship_images' => $ship->images->pluck('path')->toArray(),
            'ship_video' => $ship->youtube_link,
            'ship_desc' => $ship->desc,
            'ship_techs' => $ship->techs_arr,
            'ship_onboards' => $ship->onboards_arr,
            'ship_scheme' => @$ship->scheme[0]->path,
            'ship_cabins' => $ship->decksWithCabins(),
            'ship_status' => @$ship->status->name,
            'permanent_discounts' => $ship->permanent_discounts,
            'temporary_discounts' => $ship->temporary_discounts,
            'price_contains' => $ship->add_a,
            'additionally_paid' => $ship->add_b,
        ];

        return View::make('mcmraak.rivercrs::prok.ship', $data)->render();
    }

    # Получить описание статуса теплохода
    # http://azimut.dc/prok/api/getShipStatusDesc?name=%D0%9F%D1%80%D0%B5%D0%BC%D0%B8%D1%83%D0%BC
    function getShipStatusDesc()
    {
        $name = Input::get('name');
        $desc = @ShipStatus::where('name', $name)->first()->desc;
        if(!$desc) return;

        $this->json([
            'desc' => $desc
        ]);
    }


    private $place_names = [
        1 => 'Одно',
        2 => 'Двух',
        3 => 'Трёх',
        4 => 'Четырёх',
        5 => 'Пяти',
        6 => 'Шести',
        7 => 'Семи',
        8 => 'Восьми',
        9 => 'Девяти',
        10 => 'Десяти',
    ];

    # http://azimut.dc/prok/api/openCabin
    function openCabin()
    {
        $exist = new Exist;

        $data = Input::all();


        //$cache = new Cabox('test');
        //$cache->put('openCabin', $data);
        //$data = $cache->get('openCabin');



        //$checkin_id = intval($data['checkin_id']);
        $category_id = intval($data['c']);
        $room_number = $data['n'];
        $free_status = $data['f'];
        $deck_id = $data['d'];
        $check = $data['check'];

        $cabin = Cabin::find($category_id);

        $room_data = [
            'room_number' => $room_number,
            'free_status' => $free_status,
            #'cabin_data' => $exist->getCabinData($checkin_id, $room_number, $deck_id),
            'free_status' => $free_status,
            'check' => $check,
            'deck_id' => $deck_id,
        ];


        $html = View::make('mcmraak.rivercrs::prok.cabin_modal', [
            'cabin' => $cabin,
            'placenames' => $this->place_names,
            'room_data' => $room_data
        ])->render();



        $this->json([
            'html' => $html
        ]);

    }

    function openCabinInfo()
    {
        $cabin_id = Input::get('cabin_id');
        $cabin = Cabin::find($cabin_id);
        $html = View::make('mcmraak.rivercrs::prok.cabin_modal', [
            'cabin' => $cabin,
            'placenames' => $this->place_names,
            'room_data' => null
        ])->render();

        $this->json([
            'html' => $html
        ]);
    }

}
