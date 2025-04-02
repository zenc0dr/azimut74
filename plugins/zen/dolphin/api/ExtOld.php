<?php namespace Zen\Dolphin\Api;

use Zen\Dolphin\Classes\Core;
use Zen\Dolphin\Models\Tour;
use Exception;

class ExtOld extends Core
{
    private $token;
    private $query;
    private $list_type; # Тип выводимого контента: catalog, schedule, offers
    private $from_saratov; # Опция "Из Саратова"
    private $date;
    private $tour_id;

    private $result_items = [];
    private $labels_data;
    private $allowed_dates;
    private $reduct_desc;

    private $total = 0;

    private bool $debug = false;

    # Документация: http://8ber.ru/s/cjs
    # Отладка:
    # http://azimut.dc/zen/dolphin/api/ext:search
    public function search($debug = null)
    {
        if ($debug) {
            $this->debug = true;
        }

        # Вводные данные для поиска
        $this->query = $this->input('query');
        $this->token = $this->input('token');

        # Тип отдаваемого списка : catalog | schedule | offers
        $this->list_type = $this->input('list_type');

        # Дополнительные вводные данные для $list_type == 'offers'
        $this->date = $this->input('date');
        $this->tour_id = $this->input('tour_id'); # tour_id == tour_eid

        # Выезды только из Саратова
        $this->from_saratov = $this->input('from_saratov');


        if ($debug) {
            if (isset($debug['query'])) {
                $this->query = $debug['query'];
            }

            if (isset($debug['token'])) {
                $this->token = $debug['token'];
            }

            if (isset($debug['list_type'])) {
                $this->list_type = $debug['list_type'];
            }

            if (isset($debug['date'])) {
                $this->date = $debug['date'];
            }

            if (isset($debug['tour_id'])) {
                $this->tour_id = $debug['tour_id'];
            }

            if (isset($debug['from_saratov'])) {
                $this->from_saratov = $debug['from_saratov'];
            }
        }

        # Прерывание в случае отсутсвия токена
        if (!$this->token) {
            $this->dropError('Отстутсвует токен');
        }

        $this->localQuery();
        $this->output();
    }

    # Локальный запрос
    private function localQuery()
    {
        # Кэш для локального хранения данных
        $cache = $this->cache('dolphin.service');
        $token = $this->token;
        $list_type = $this->list_type;
        $query = $this->query;
        $tour_id = $this->tour_id;
        $date = $this->date;
        $from_saratov = $this->from_saratov;

        # Дополняем первоначальный запрос опцией "Из Саратова"
        $query['from_saratov'] = (bool) $from_saratov;


        if ($list_type !== 'offers') {
            $cache->put("$token:query", $query);

            # Сохраняем первоначальный запрос для offers
            $cached_data = $cache->get("$token:$list_type");

            if ($cached_data && !$from_saratov) {
                $this->result_items = $cached_data['items'];
                $this->labels_data = $cached_data['labels'];
            } else {

                $local_search = $this->store('LocalSearch')->search($query, $list_type, $token);

                $this->result_items = $local_search->getResultItems();
                $this->labels_data = $local_search->getLabelsData();

                # Кэш сохранять только если не стоит пометки из Саратова
                if (!$from_saratov) {
                    $cache->put("$token:$list_type", [
                        'items' => $this->result_items,
                        'labels' => $this->labels_data
                    ]);
                }
            }
        } else {
            # При первом запросе нет $query а при повторных есть
            if (!$this->query) {
                $this->query = $query = $cache->get("$token:query");
            }

            # Примешиваем динамические данные
            $query['tour_id'] = $tour_id;
            $query['date'] = $date;

            $local_search = $this->store('LocalSearch')->search($query, $list_type, $token);
            $this->result_items = $local_search->getResultItems();
            $this->allowed_dates = $local_search->getAllowedDates();
            $this->reduct_desc = $local_search->getReductDesc();
        }
    }

    private function output()
    {
        $this->sortingRecords();

        $output = [
            'query' => $this->query,
            'items' => $this->result_items,
            'allowed_dates' => $this->allowed_dates,
            'labels_data' => $this->labels_data,
            'total' => $this->total,
            'success' => true,
            'reduct_desc' => $this->reduct_desc,
        ];

        # Для отладки
        if ($this->debug) {
            dd($output);
        }

        $this->json($output);
    }

    # Сортировка вывода
    private function sortingRecords()
    {
        if (!$this->result_items) {
            return;
        }

        if ($this->list_type == 'catalog') {
            $this->result_items = collect($this->result_items)
                ->sortBy('price')
                ->toArray();
        }

        if ($this->list_type == 'schedule') {
            $this->result_items = collect($this->result_items)
                ->sortBy('tour_name')
                ->sortBy('price')
                ->sortBy('timestamp')
                ->toArray();
        }

        if ($this->list_type == 'offers') {
            $this->result_items = collect($this->result_items)
                ->sortBy('price')
                ->toArray();
        }

        $this->result_items = array_values($this->result_items);
    }

    # http://azimut.dc/zen/dolphin/api/ext:tour?tour_eid=29165
    public function tour()
    {
        $tour_id = $this->input('tour_id');

        if ($tour_id) {
            $tour = Tour::find($tour_id);

            if (!$tour) {
                return;
            }

            $this->json([
                'tour_name' => $tour->name
            ]);
        }
    }
}
