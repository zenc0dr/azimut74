<?php namespace Mcmraak\Rivercrs\Classes;

use Carbon\Carbon;
use DB;
use Mcmraak\Rivercrs\Classes\CacheSettings as Settings;
use Mcmraak\Rivercrs\Models\Checkins;

class RivercrsCheckin
{
    public static function checkin(Checkins $checkin)
    {
        $ship = $checkin->motorship;
        $checkin_prices = app('Mcmraak\Rivercrs\Classes\Exist')->get($checkin, 'array');

        $data = [
            #'meta_title' => $checkin->metatitle ?? $ship->metatitle,
            'meta_title' => self::generateSeoTitle($checkin), # Новый тип https://docs.os3.pro/stage/build/desktop/?r=wx9tahx
            #'meta_description' => $ship->metadesc,
            'meta_description' => self::generateSeoDescription($checkin),
            'meta_keywords' => $ship->metakey,
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
            'ship_status' => $ship->status->name ?? null,
            'ship_status_desc' => $ship->status->desc ?? null,
            'permanent_discounts' => $ship->permanent_discounts,
            'temporary_discounts' => $ship->temporary_discounts,
            'social_discounts' => $ship->social_discounts,
            'booking_discounts' => $ship->booking_discounts,
            'json_data' => $checkin->json_data,
            'date' => $checkin->createDatesArray(),
            'waybill' => $checkin->getWaybill(' - '),
            'price_start' => $checkin->startPrice,
            'days' => $checkin->days . ' ' . $checkin->incline(['день', 'дня', 'дней'], $checkin->days),
            'price_contains' => $ship->add_a,
            'additionally_paid' => $ship->add_b,
            'statuses' => Settings::get('cabin_statuses'),
            'schedule' => self::getSchedule($checkin),
            'checkin_prices' => json_encode($checkin_prices, 256),
            'breadcrumbs' => [
                ['/', 'Главная'],
                ['/russia-river-cruises', 'Речные круизы'],
            ]
        ];

        return $data;
    }

    # Метатег title основанный на задаче https://docs.os3.pro/stage/build/desktop/?r=wx9tahx
    # Речной круиз (дата начала) — (дата окончания) — Теплоход (Название теплохода)
    private static function generateSeoTitle(object $checkin)
    {
        $date_of = master()->carbon($checkin->date)->format("d.m.Y");
        $date_to = master()->carbon($checkin->dateb)->format("d.m.Y");
        $ship_name = $checkin->motorship->cleanSelfName();
        return "Речной круиз $date_of — $date_to — Теплоход $ship_name";
    }

    # Речной круиз (дата начала) — (дата окончания) по маршруту: (маршрут) на теплоходе (Название теплохода).
    # Цена от (стоимость) руб. без учета скидок и акций. Онлайн-бронирование.
    private static function generateSeoDescription(object $checkin)
    {
        # Дата от
        $date_of = master()->carbon($checkin->date)->format("d.m.Y");
        # Дата до
        $date_to = master()->carbon($checkin->dateb)->format("d.m.Y");
        # Чистое имя теплохода
        $ship_name = $checkin->motorship->cleanSelfName();
        $waybill = $checkin->getWaybillLine();
        
        # Получаем минимальную цену из mcmraak_rivercrs_pricing
        $min_price = DB::table('mcmraak_rivercrs_pricing')
            ->where('checkin_id', $checkin->id)
            ->where('price_a', '>', 0)
            ->min('price_a');
        
        # Если в pricing нет цены, берем из nprices
        if (!$min_price || $min_price == 0) {
            $min_price = DB::table('mcmraak_rivercrs_nprices')
                ->where('checkin_id', $checkin->id)
                ->where('price', '>', 0)
                ->min('price');
        }
        
        $min_price = $min_price ? intval($min_price) : 0;
        
        # Формируем описание согласно шаблону
        $description = "Речной круиз $date_of — $date_to по маршруту: $waybill на теплоходе $ship_name.";
        if ($min_price > 0) {
            $description .= " Цена от $min_price руб. без учета скидок и акций.";
        }
        $description .= " Онлайн-бронирование.";

        return $description;
    }

    # Получить расписание
    private static function getSchedule($checkin)
    {
        $eds_code = $checkin->eds_code;

        if ($eds_code == 'volga') {
            # TODO: Расписание Волги делается из XLS-файла, на данный момент его давно не обновляли
            //$data = \Mcmraak\Rivercrs\Controllers\VolgaSettings::getVolgaExcursion($checkin);
            return null;
        }

        # http://azimut74/cruise/2449
        if ($eds_code == 'germes') {
            $data = (new \Mcmraak\Rivercrs\Classes\Exist\GermesSchelude)->render($checkin, true);
            # arr: trace_table, excursion_table
            if (empty($data['trace_table'])) {
                return null;
            }
            return self::germesSchedule($data['trace_table']);
        }

        # http://azimut74/cruise/1
        if ($eds_code == 'waterway') {
            return self::waterwaySchedule($checkin->desc_1);
        }

        # http://azimut74/cruise/1681
        if ($eds_code == 'gama') {
            return self::sheduleStandartTableToArray($checkin->desc_1);
        }

        return self::sheduleStandartTableToArray($checkin->desc_1);
    }

    # Расписание гермеса
    private static function germesSchedule($trace_table): array
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

    # Расписание водохода
    private static function waterwaySchedule($table)
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

            # Вырезание ссылок из контента
            $desc = preg_replace('/<a[^>]+>([^<]+)<\/a>/ui', '$1', $desc);
            $desc = preg_replace('/([^ ]{0,1}Водоход[ъЪ]{0,1}[^ ]{0,1})/ui', ' ', $desc);
            $desc = preg_replace('/ {2,}/ui', ' ', $desc);

            $schedule->desc = $desc;

            $schedule->addDay();

        }
        return $schedule->getSchedule();
    }

    # Расписание стандартное
    private static function sheduleStandartTableToArray($table)
    {
        $table = str_replace("\n", '', $table);
        $table = explode('<tr>', $table);

        $schedule = new ScheduleBuilder;
        $schedule->action = false;
        foreach ($table as $row) {
            $row = trim($row);
            if (strpos($row, '<td>') !== 0) {
                continue;
            }

            preg_match_all('/<td>([^<>]*)<\/td>/', $row, $m);
            $row = @$m[1];

            if (!$row) {
                continue;
            }

            $schedule->date_arrive = @$row[0];
            $schedule->town = @$row[1];
            $schedule->time_arrive = @$row[2];
            $schedule->time_diff = @$row[3];
            $schedule->time_depart = @$row[4];
            $schedule->addDay();
        }

        return $schedule->getSchedule();
    }
}
