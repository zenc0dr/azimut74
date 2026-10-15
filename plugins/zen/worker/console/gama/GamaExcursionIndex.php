<?php namespace Zen\Worker\Console\gama;

/**
 * Экскурсии Гамы: справочник dir_trip.xml и привязка стоянок navigation_{id}_trip.xml.
 * Текст без HTML-тегов: его кладут в ячейку таблицы, которую разбирает вкладка «Маршрут».
 */
class GamaExcursionIndex
{
    private $trips = [];
    private $byPath = [];

    public static function fromDirectory($directory)
    {
        $index = new self();
        $directory = rtrim((string) $directory, '/');
        $catalog = $directory . '/dir_trip.xml';
        if (is_file($catalog)) {
            $index->loadCatalog($catalog);
        }

        foreach (glob($directory . '/navigation_*_trip.xml') ?: [] as $file) {
            if (preg_match('/navigation_(\d+)_trip\.xml$/', $file, $match)) {
                $index->loadRouteFile($match[1], $file);
            }
        }

        return $index;
    }

    public function textForPath($navigationId, $pathId)
    {
        $navigationId = (string) $navigationId;
        $pathId = (string) (int) $pathId;
        $tripIds = $this->byPath[$navigationId][$pathId] ?? [];
        if (!$tripIds) {
            return '';
        }

        $chunks = [];
        foreach ($tripIds as $tripId) {
            if (!isset($this->trips[$tripId])) {
                continue;
            }
            $chunk = $this->formatTrip($this->trips[$tripId]);
            if ($chunk !== '') {
                $chunks[] = $chunk;
            }
        }

        return implode(' | ', $chunks);
    }

    private function loadCatalog($file)
    {
        $xml = @simplexml_load_file($file);
        if (!$xml) {
            return;
        }

        foreach ($xml->TripList as $list) {
            foreach ($list->Trip as $trip) {
                $id = trim((string) $trip['id']);
                if ($id === '') {
                    continue;
                }
                $this->trips[$id] = [
                    'name' => trim((string) $trip['name']),
                    'description' => (string) $trip['description'],
                    'price' => (string) $trip['cost_office_std'],
                ];
            }
        }
    }

    private function loadRouteFile($navigationId, $file)
    {
        $xml = @simplexml_load_file($file);
        if (!$xml) {
            return;
        }

        foreach ($xml->TripRouteList as $list) {
            foreach ($list->Item as $item) {
                $pathId = (string) (int) $item['path_id'];
                $tripId = trim((string) $item['trip_id']);
                if ($pathId === '0' || $tripId === '') {
                    continue;
                }
                $this->byPath[(string) $navigationId][$pathId][] = $tripId;
            }
        }
    }

    private function formatTrip(array $trip)
    {
        $name = $this->plain($trip['name']);
        $body = $this->plain($trip['description']);
        $price = (int) round((float) str_replace(',', '.', $trip['price']));

        $head = $name;
        if ($price > 0) {
            $head = $head === '' ? $price . ' руб.' : $head . ' — ' . $price . ' руб.';
        }
        if ($body === '') {
            return $head;
        }

        if ($head === '') {
            return $body;
        }
        $glue = preg_match('/[.!?…]$/u', $head) ? ' ' : '. ';

        return $head . $glue . $body;
    }

    private function plain($value)
    {
        $text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $text);
        $text = str_replace(["\xc2\xa0", "\r", "\n", "\t"], ' ', $text);
        $text = str_replace(['<', '>'], '', $text);
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim($text);
    }
}
