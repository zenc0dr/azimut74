<?php

if (!function_exists('cursor')) {
    /**
     * Отладочная функция cursor() для дампа переменных
     * 
     * @param mixed ...$vars Переменные для дампа
     * @param array $options Опции:
     *   - die_on_exec (bool) - остановить выполнение после дампа
     *   - label (string) - метка для дампа
     * 
     * @return string ID дампа
     * 
     * Примеры:
     * cursor($var1, $var2);
     * cursor($data, ['die_on_exec' => true, 'label' => 'checkpoint1']);
     */
    function cursor(...$vars) {
        static $dumpCounter = 0;
        
        // Извлекаем опции из последнего аргумента, если это массив с ключами
        $options = [];
        if (!empty($vars) && is_array(end($vars)) && 
            (isset(end($vars)['die_on_exec']) || isset(end($vars)['label']))) {
            $options = array_pop($vars);
        }
        
        $dieOnExec = $options['die_on_exec'] ?? false;
        $label = $options['label'] ?? null;
        
        // Генерируем уникальный ID
        $dumpId = 'cursor_' . uniqid('', true);
        $dumpCounter++;
        
        // Подготавливаем данные
        $dumpData = [
            'id' => $dumpId,
            'counter' => $dumpCounter,
            'timestamp' => date('Y-m-d H:i:s'),
            'microtime' => microtime(true),
            'label' => $label,
            'backtrace' => array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), 1, 5),
            'vars' => []
        ];
        
        // Обрабатываем переменные
        foreach ($vars as $index => $var) {
            $dumpData['vars']["var_{$index}"] = [
                'type' => gettype($var),
                'value' => $var,
                'is_object' => is_object($var),
                'is_array' => is_array($var),
                'is_null' => is_null($var),
                'size' => is_array($var) ? count($var) : (is_string($var) ? strlen($var) : null)
            ];
        }
        
        // Сохраняем дамп
        $dumpDir = storage_path('cursor_dumps');
        if (!is_dir($dumpDir)) {
            mkdir($dumpDir, 0775, true);
        }
        
        $dumpFile = $dumpDir . '/' . $dumpId . '.json';
        file_put_contents(
            $dumpFile, 
            json_encode($dumpData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );
        
        chmod($dumpFile, 0664);
        
        // Выводим информацию
        $message = "CURSOR: {$dumpId}";
        if ($label) {
            $message .= " [{$label}]";
        }
        $message .= " - " . count($vars) . " var(s)";
        
        if (php_sapi_name() === 'cli') {
            echo $message . PHP_EOL;
        } else {
            error_log($message);
        }
        
        // Если die_on_exec, останавливаем выполнение
        if ($dieOnExec) {
            error_log("CURSOR: Execution stopped after dump {$dumpId}");
            exit(0);
        }
        
        return $dumpId;
    }
}

