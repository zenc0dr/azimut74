<?php namespace Zen\Uon\Api;

use Zen\Uon\Classes\Core;
use Log;
use View;

class Data extends Core
{
    # http://azimut.dc/zen/uon/api/data:get?request=4-5:14980,12-15:14982,19-20:14983 // {места}:{Номер заказа}
    public function get()
    {
        $styler = intval($this->input('styler'));

        $time = microtime(true);
        $query_count = 5; // Максимальное количество попыток в секунду
        $ms_max = 1000000; // 1 секунда в микросекундах

        $request_ids = $this->input('request');
        $request_ids = explode(',', $request_ids);

        $tour_name = $this->input('tour_name');
        $dates = $this->input('dates');
        $scheme = $this->input('scheme');

        $users = [];

        foreach ($request_ids as $request_id) {
            if (!boolval(request()->get('cache'))) {
                sleep(1);
            }

            $request_id = explode(':', $request_id);

            $places = explode('-', $request_id[0]);

            $request_id = $request_id[1];

            $response = $this->api()->getRequest($request_id)->getArray();

            # begin::Счётчик #
            $query_count--;
            if ($query_count == 0) {
                $ms_diff = microtime(true) - $time; // Сколько прошло времени
                if ($ms_diff < $ms_max) {
                    if (!boolval(request()->get('cache'))) {
                        usleep($ms_max - $ms_diff + 1000);
                    }
                }
                $time = microtime(true);
                $query_count = 5;
            }
            # end::Счётчик #

            $tourists = $response['tourists'];
            $response_code = $response['id_internal'];

            $response_code = explode('-', $response_code);

            if (isset($response_code[1])) {
                $response_code = trim($response_code[1]);
            } else {
                $response_code = trim($response_code[0]);
            }

//                if(!$tourists) {
//                    $err = "UON API Запрос $request_id не вернул данные, задача прервана";
//                    \Log::debug($err);
//                    die($err);
//                }

            if ($tourists) {
                $i = 0;
                foreach ($tourists as $tourist) {
                    $user = $this->api()->getTourist($tourist['u_id'])->getArray();
                    # begin::Счётчик #
                    $query_count--;
                    if ($query_count == 0) {
                        $ms_diff = microtime(true) - $time; // Сколько прошло времени
                        if ($ms_diff < $ms_max) {
                            if (!boolval(request()->get('cache'))) {
                                usleep($ms_max - $ms_diff + 1000);
                            }
                        }
                        $time = microtime(true);
                        $query_count = 5;
                    }
                    # end::Счётчик #

                    $extended_fields = @$user['extended_fields'];
                    $landing = null;
                    if ($extended_fields) {
                        foreach ($extended_fields as $field) {
                            if ($field['id'] == 45877) {
                                $landing = $field['value'];
                            }
                        }
                    }

                    $t_name = join(' ', [$tourist['u_surname'], $tourist['u_name'], $tourist['u_sname']]);

                    if (!isset($places[$i])) {
                        $error = "Ошибка целостности данных печатной формы для туриста: $t_name, нет места.";
                        die($error);
                    }

                    $users[] = [
                        'request_code' => $response_code,
                        'name' => $t_name,
                        'phone' => $tourist['u_phone_mobile'],
                        'landing' => $landing,
                        'place' => $places[$i]
                    ];

                    $i++;
                }
            } else {
                $users[] = [
                    'request_code' => $response_code,
                    'name' => join(' ', [$response['client_surname'], $response['client_name'], $response['client_sname']]),
                    'phone' => $response['client_phone_mobile'],
                    'landing' => '',
                    'place' => $places[0]
                ];
            }


        }

        $scheme = $this->handleScheme($scheme, $users);
        # $scheme = $this->cleanEmptyRows($scheme, true);


        $html = View::make('zen.uon::print', [
            'dates' => $dates,
            'tour_name' => $tour_name,
            'scheme' => $scheme,
            'styler' => $styler
        ]);

        return response($html)->header('Access-Control-Allow-Origin', '*');
    }

    public function cleanEmptyRows($rows, $only_last)
    {
        $new_rows = [];
        $count = count($rows);
        $i = 0;
        foreach ($rows as $row) {
            $i++;
            $empty_row = true;
            foreach ($row as $cell) {
                if ($cell['user']) {
                    $empty_row = false;
                }
            }
            if ($only_last) {
                if ($i == $count) {
                    if (!$empty_row) {
                        $new_rows[] = $row;
                    }
                } else {
                    $new_rows[] = $row;
                }
            } else {
                if (!$empty_row) {
                    $new_rows[] = $row;
                }
            }
        }

        return $new_rows;
    }

    public function handleScheme($scheme, $users)
    {
        $scheme_rows = explode('|', $scheme);
        $out_rows = [];

        foreach ($scheme_rows as $scheme_row) {
            $out_row = [];
            $row_cells = explode('@', $scheme_row);
            foreach ($row_cells as $row_cell) {
                $out_row[] = $this->placeUser($row_cell, $users);
            }
            $out_rows[] = $out_row;
        }

        return $out_rows;
    }

    public function placeUser($row_cell, $users): array
    {
        if (!$row_cell) {
            $row_cell = null;
        }

        $user = null;
        foreach ($users as $user_data) {
            if ($user_data['place'] == $row_cell) {
                $user = $user_data;
            }
        }
        return [
            'place' => $row_cell,
            'user' => $user
        ];
    }
}
