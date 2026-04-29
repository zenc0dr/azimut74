<?php namespace Mcmraak\Rivercrs\Classes;

/**
 * Группировка расписания Водохода по календарным дням (несколько точек маршрута в один день).
 * Источник — HTML-таблица в desc_1 (формат парсера zen/worker).
 */
class WaterwayScheduleGrouped
{
    /**
     * Разбор desc_1 → плоский массив строк расписания (как у ScheduleBuilder по одной строке таблицы).
     *
     * @return array<int, array<string,mixed>>
     */
    public static function parseDesc1ToFlatRows(string $table): array
    {
        $table = str_replace('</tr>', '', $table);
        $table = str_replace('</td>', '', $table);

        $table_lines = explode('<tr>', $table);
        $rows = [];

        foreach ($table_lines as $table_line) {
            if (strpos($table_line, '<td>') !== 0) {
                continue;
            }

            if (strpos($table_line, '<td>День') === 0) {
                continue;
            }

            $table_line = explode('<td>', $table_line);

            $schedule = new ScheduleBuilder;

            preg_match('/^(\d+)[ |]<br>/m', @$table_line[1], $m);
            $schedule->day = @$m[1];
            preg_match('/<br>(\d+\.\d+\.\d+)<br>/', @$table_line[1], $m);
            $date = @$m[1];
            preg_match('/(\d+:\d+)/', @$table_line[1], $m);
            $time = @$m[1];
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

            $desc = preg_replace('/<a[^>]+>([^<]+)<\/a>/ui', '$1', $desc);
            $desc = preg_replace('/([^ ]{0,1}Водоход[ъЪ]{0,1}[^ ]{0,1})/ui', ' ', $desc);
            $desc = preg_replace('/ {2,}/ui', ' ', $desc);

            $schedule->desc = $desc;

            $schedule->addDay();
            $built = $schedule->getSchedule();
            if (!empty($built[0])) {
                $built[0]['_time_cell_html'] = @$table_line[1];
                $rows[] = $built[0];
            }
        }

        return $rows;
    }

    /**
     * Группировка по календарной дате (строка dd.mm.yyyy из date_arrive / date_depart).
     *
     * @return array<int, array<string,mixed>>
     */
    public static function buildSchedule(string $table): array
    {
        $flat = self::parseDesc1ToFlatRows($table);
        if (empty($flat)) {
            return [];
        }

        $groups = [];
        $order = [];

        foreach ($flat as $row) {
            $dk = $row['date_arrive'] ?: $row['date_depart'];
            if (!$dk) {
                continue;
            }
            if (!isset($groups[$dk])) {
                $groups[$dk] = [];
                $order[] = $dk;
            }
            $groups[$dk][] = $row;
        }

        $order = array_values(array_unique($order));

        $out = [];
        $displayDay = 1;

        foreach ($order as $dk) {
            $segmentRows = $groups[$dk];
            $first = $segmentRows[0];

            $merged = $first;
            $merged['day'] = $displayDay++;
            $merged['segments'] = [];

            foreach ($segmentRows as $r) {
                $merged['segments'][] = [
                    'town' => $r['town'],
                    'desc' => $r['desc'],
                    'time_arrive' => $r['time_arrive'],
                    'time_depart' => $r['time_depart'],
                    'time_diff' => $r['time_diff'],
                    'dof' => $r['dof'] ?? null,
                    'date_arrive' => $r['date_arrive'],
                    'date_depart' => $r['date_depart'],
                    'action' => $r['action'],
                    'time_cell_html' => $r['_time_cell_html'] ?? '',
                ];
            }

            unset($merged['_time_cell_html']);

            $out[] = $merged;
        }

        return $out;
    }

    /**
     * HTML для модального окна «График движения»: таблица с заголовком календарного дня и подстроками по точкам.
     */
    public static function toModalHtml(?string $desc1): string
    {
        if ($desc1 === null || $desc1 === '') {
            return '';
        }

        $flat = self::parseDesc1ToFlatRows($desc1);
        if (empty($flat)) {
            return $desc1;
        }

        $groups = [];
        $order = [];
        foreach ($flat as $row) {
            $dk = $row['date_arrive'] ?: $row['date_depart'];
            if (!$dk) {
                continue;
            }
            if (!isset($groups[$dk])) {
                $groups[$dk] = [];
                $order[] = $dk;
            }
            $groups[$dk][] = $row;
        }
        $order = array_values(array_unique($order));

        $lines = [];
        $lines[] = '<table class="ww-table ww-table--grouped"><tbody>';
        $lines[] = '<tr><td>День</td><td>Стоянка</td><td>Программа дня</td></tr>';

        $displayDay = 1;
        foreach ($order as $dk) {
            $rows = $groups[$dk];
            $first = $rows[0];
            $dateObj = $first['date'] ?? null;
            $dowShort = is_object($dateObj) && method_exists($dateObj, 'format')
                ? ['вс', 'пн', 'вт', 'ср', 'чт', 'пт', 'сб'][$dateObj->dayOfWeek]
                : '';

            $lines[] = '<tr class="ww-day-head"><td colspan="3"><strong>День ' . $displayDay . '</strong> — '
                . htmlspecialchars($dk, ENT_QUOTES, 'UTF-8')
                . ($dowShort !== '' ? ' (' . htmlspecialchars($dowShort, ENT_QUOTES, 'UTF-8') . ')' : '')
                . '</td></tr>';

            foreach ($rows as $r) {
                $timeHtml = self::timeCellOnlyHtml($r['_time_cell_html'] ?? '');
                $port = htmlspecialchars((string)$r['town'], ENT_QUOTES, 'UTF-8');
                $prog = $r['desc'] ?? '';
                $lines[] = '<tr><td>' . $timeHtml . '</td><td>' . $port . '</td><td>' . $prog . '</td></tr>';
            }

            $displayDay++;
        }

        $lines[] = '</tbody></table>';

        return implode("\n", $lines);
    }

    /**
     * Убираем из первой ячейки номер дня и дату (они вынесены в строку-заголовок группы).
     */
    private static function timeCellOnlyHtml(string $cellHtml): string
    {
        $cellHtml = preg_replace('/^\d+\s*<br>\s*/is', '', $cellHtml);
        $cellHtml = preg_replace('/\d{2}\.\d{2}\.\d{4}\s*<br>\s*/', '', $cellHtml, 1);
        return trim($cellHtml);
    }
}
