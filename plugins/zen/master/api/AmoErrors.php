<?php

namespace Zen\Master\Api;

use Zen\Master\Classes\Services\OutboundHttp;
use Zen\Master\Models\LogModel;

class AmoErrors
{
    /**
     * GET /master.api.AmoErrors.feed?token=&since_id=&limit=
     */
    public function feed()
    {
        if (strtolower(request()->method()) === 'options') {
            return $this->cors('', 204);
        }

        $expected = (string) env('AMO_ERRORS_FEED_TOKEN', '');
        $token = (string) (request('token') ?: request()->header('X-Amo-Errors-Token', ''));
        if ($expected === '' || !hash_equals($expected, $token)) {
            return $this->cors(['error' => 'forbidden'], 403);
        }

        $sinceId = max(0, intval(request('since_id', 0)));
        $limit = intval(request('limit', 50));
        if ($limit < 1) {
            $limit = 50;
        }
        if ($limit > 100) {
            $limit = 100;
        }

        $rows = LogModel::where('event_name', OutboundHttp::EVENT_FAIL)
            ->where('id', '>', $sinceId)
            ->orderBy('id', 'asc')
            ->limit($limit)
            ->get();

        $items = [];
        $lastId = $sinceId;
        foreach ($rows as $row) {
            $data = [];
            if (is_string($row->data) && $row->data !== '') {
                $decoded = json_decode($row->data, true);
                if (is_array($decoded)) {
                    $data = $decoded;
                }
            }
            $item = $data;
            $item['id'] = (int) $row->id;
            $item['created_at'] = $row->created_at ? (string) $row->created_at : null;
            $items[] = $item;
            $lastId = (int) $row->id;
        }

        return $this->cors([
            'last_id' => $lastId,
            'items' => $items,
        ]);
    }

    private function cors($payload, int $status = 200)
    {
        $body = is_string($payload) ? $payload : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return response($body, $status)
            ->header('Content-Type', 'application/json; charset=utf-8')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, X-Amo-Errors-Token')
            ->header('Access-Control-Max-Age', '86400');
    }
}
