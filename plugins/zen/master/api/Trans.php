<?php namespace Zen\Master\Api;

use DB;

class Trans
{
    # /master.api.Trans.save
    public function save()
    {
        $path = storage_path('trans_places_main_count.json');
        $rows = DB::table('mcmraak_rivercrs_cabins')
            ->select('id', 'places_main_count')
            ->orderBy('id')
            ->get();
        
        $data = [];
        foreach ($rows as $row) {
            $id = (int)$row->id;
            $places = (int)$row->places_main_count;
            $data[$id] = $places;
        }
        
        $payload = [
            'generated_at' => date('c'),
            'count' => count($data),
            'data' => $data
        ];
        
        file_put_contents(
            $path,
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            LOCK_EX
        );
        
        echo json_encode([
            'ok' => true,
            'path' => $path,
            'count' => count($data)
        ], JSON_UNESCAPED_UNICODE);
    }

    # /master.api.Trans.restore
    public function restore()
    {
        $path = storage_path('trans_places_main_count.json');
        if (!file_exists($path)) {
            echo json_encode([
                'ok' => false,
                'error' => "Файл не найден: $path"
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        $raw = file_get_contents($path);
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            echo json_encode([
                'ok' => false,
                'error' => 'Некорректный JSON в файле'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        $data = $payload['data'] ?? $payload;
        if (!is_array($data) || !$data) {
            echo json_encode([
                'ok' => false,
                'error' => 'Пустой набор данных для восстановления'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        $ids = array_keys($data);
        $total = 0;
        $updated = 0;
        
        foreach (array_chunk($ids, 500) as $chunk) {
            $caseParts = [];
            $inIds = [];
            foreach ($chunk as $id) {
                $id = (int)$id;
                if ($id <= 0) {
                    continue;
                }
                $places = (int)($data[$id] ?? 0);
                if ($places <= 0) {
                    continue;
                }
                $caseParts[] = "WHEN $id THEN $places";
                $inIds[] = $id;
            }
            
            if (!$inIds) {
                continue;
            }
            
            $total += count($inIds);
            $existingCount = DB::table('mcmraak_rivercrs_cabins')
                ->whereIn('id', $inIds)
                ->count();
            
            $caseSql = implode(' ', $caseParts);
            $inSql = implode(',', $inIds);
            $sql = "UPDATE mcmraak_rivercrs_cabins SET places_main_count = CASE id $caseSql END WHERE id IN ($inSql)";
            DB::statement($sql);
            
            $updated += $existingCount;
        }
        
        echo json_encode([
            'ok' => true,
            'path' => $path,
            'total_ids' => $total,
            'updated_rows' => $updated
        ], JSON_UNESCAPED_UNICODE);
    }
}