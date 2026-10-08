<?php namespace Zen\Worker\Console;

/**
 * HTML-таблица расписания, которую разбирает стандартный парсер вкладки «Маршрут».
 * Колонки: дата, город, прибытие, стоянка в минутах, отправление, описание.
 */
class RouteScheduleHtml
{
    public static function render(array $rows): string
    {
        if (count($rows) < 2) {
            return '';
        }

        $html = '<table class="rivercrs-gama-schelude-table"><tbody>';
        $html .= '<tr><th>Дата</th><th>Город</th><th>Прибытие</th><th>Стоянка</th><th>Отправление</th><th>Описание</th></tr>';

        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach (['date', 'town', 'arrival', 'stay', 'departure', 'description'] as $key) {
                $html .= '<td>' . self::cell($row[$key] ?? '') . '</td>';
            }
            $html .= '</tr>';
        }

        return $html . '</tbody></table>';
    }

    public static function fromVolgaPoint(array $point): ?array
    {
        $arrival = self::dateTime($point['arrival'] ?? '');
        $departure = self::dateTime($point['departure'] ?? '');
        $moment = $arrival ?: $departure;
        $town = trim((string) ($point['town_name'] ?? ''));

        if (!$moment || $town === '') {
            return null;
        }

        return [
            'date' => $moment->format('d.m.Y'),
            'town' => $town,
            'arrival' => $arrival ? $arrival->format('H:i') : '',
            'stay' => self::stayMinutes($arrival, $departure, $point['stay_time'] ?? ''),
            'departure' => $departure ? $departure->format('H:i') : '',
            'description' => '',
        ];
    }

    public static function fromInfoflotTimetable(array $timetable): string
    {
        $rows = [];

        foreach ($timetable as $point) {
            if (!is_array($point)) {
                continue;
            }

            $arrival = self::dateTime($point['dateArrival'] ?? '');
            $departure = self::dateTime($point['dateDeparture'] ?? '');
            $moment = $arrival ?: $departure;
            $town = trim((string) ($point['place'] ?? ($point['city']['name'] ?? '')));

            if (!$moment || $town === '') {
                continue;
            }

            $rows[] = [
                'date' => $moment->format('d.m.Y'),
                'town' => $town,
                'arrival' => $arrival ? $arrival->format('H:i') : '',
                'stay' => self::stayMinutes($arrival, $departure, ''),
                'departure' => $departure ? $departure->format('H:i') : '',
                'description' => self::infoflotDescription($point),
            ];
        }

        return self::render($rows);
    }

    private static function infoflotDescription(array $point): string
    {
        $parts = [];
        $description = trim(html_entity_decode(strip_tags((string) ($point['description'] ?? '')), ENT_QUOTES, 'UTF-8'));
        if ($description !== '') {
            $parts[] = preg_replace('/\s+/u', ' ', $description);
        }

        $excursions = $point['excursions'] ?? [];
        if (is_array($excursions)) {
            foreach ($excursions as $excursion) {
                if (!is_array($excursion)) {
                    continue;
                }
                $name = trim((string) ($excursion['name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $price = $excursion['price'] ?? $excursion['priceAdult'] ?? null;
                $parts[] = $price ? $name . ' — ' . $price . ' руб.' : $name;
            }
        }

        return trim(implode(' ', $parts));
    }

    private static function stayMinutes($arrival, $departure, $rawStay): string
    {
        if ($arrival && $departure) {
            $minutes = (int) round(($departure->getTimestamp() - $arrival->getTimestamp()) / 60);
            return $minutes > 0 ? (string) $minutes : '';
        }

        if (preg_match('/^(\d{1,3}):(\d{2})/', trim((string) $rawStay), $match)) {
            $minutes = ((int) $match[1] * 60) + (int) $match[2];
            return $minutes > 0 ? (string) $minutes : '';
        }

        return '';
    }

    private static function dateTime($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $value)
            ?: \DateTime::createFromFormat('Y-m-d H:i', $value);

        return $date ?: null;
    }

    private static function cell($value): string
    {
        return htmlspecialchars(trim((string) $value), ENT_QUOTES, 'UTF-8');
    }
}
