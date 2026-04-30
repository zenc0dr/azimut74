<?php namespace Zen\Worker\Api;

use Input;
use Zen\Worker\Classes\Convertor;
use Zen\Worker\Classes\RocketBot;

class Debug
{
    # http://azimut74/zen/worker/api/debug:cursorDump?id=cursor_xxx
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

    # Преообразует `storage/parsers_cache/volga/volga_next_url.xml` в JSON и возвращает его
    # http://azimut74/zen/worker/api/debug:xmlExplorer
    public function xmlExplorer()
    {
        $xmlFile = storage_path('parsers_cache/volga/volga_next_url.xml');
        $xmlData = file_get_contents($xmlFile);
        $xmlData = Convertor::xmlToArr($xmlData);
        dd($xmlData['free']['cruise'][0]);

    }

    # http://azimut74/zen/worker/api/debug:cursorList
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

    # http://azimut74/zen/worker/api/debug:testRocketBot
    public function testRocketBot()
    {
        #return;
        RocketBot::send('Тестирую соединение, окружение: ' . env('APP_URL'));
    }
}
