<?php namespace Zen\Worker\Api;

use Input;

class Debug
{
    # http://azimut.dc/zen/worker/api/debug:cursorDump?id=cursor_xxx
    public function cursorDump()
    {
        $dumpId = Input::get('id');

        if (!$dumpId) {
            return response()->json(['error' => 'Dump ID required'], 400, [], JSON_UNESCAPED_UNICODE);
        }

        $dumpFile = storage_path("cursor_dumps/{$dumpId}.json");

        if (!file_exists($dumpFile)) {
            return response()->json(['error' => 'Dump not found', 'id' => $dumpId], 404, [], JSON_UNESCAPED_UNICODE);
        }

        $dumpData = json_decode(file_get_contents($dumpFile), true);
        return response()->json($dumpData, 200, [], JSON_UNESCAPED_UNICODE);
    }

    # http://azimut.dc/zen/worker/api/debug:cursorList
    public function cursorList()
    {
        $dumpDir = storage_path('cursor_dumps');
        $files = is_dir($dumpDir) ? glob($dumpDir . '/cursor_*.json') : [];

        $dumps = [];
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data) {
                $dumps[] = [
                    'id' => $data['id'],
                    'timestamp' => $data['timestamp'],
                    'label' => $data['label'] ?? null,
                    'vars_count' => count($data['vars'] ?? [])
                ];
            }
        }

        // Сортируем по времени (новые первыми)
        usort($dumps, function($a, $b) {
            return strcmp($b['timestamp'], $a['timestamp']);
        });

        return response()->json(['dumps' => $dumps, 'total' => count($dumps)], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
