<?php namespace Zen\Worker\Console\transfer;

use Exception;

/**
 * Класс для логирования ошибок валидации в отдельный файл
 * Лог используется инженером парсеров для исправления ошибок на фазе 1
 */
class TransferErrorLogger
{
    /**
     * @var string Путь к файлу лога
     */
    protected $logPath;

    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->logPath = base_path('logs/transfer_errors.log');
        
        // Создаем директорию logs, если её нет
        $logDir = dirname($this->logPath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Очистка лог-файла при запуске команды
     */
    public function clearLog()
    {
        // Создаем директорию, если её нет
        $logDir = dirname($this->logPath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Очищаем файл (перезаписываем пустой строкой)
        // file_put_contents с пустой строкой создаст или очистит файл
        // Используем режим 'w' для перезаписи (без FILE_APPEND)
        file_put_contents($this->logPath, '', LOCK_EX);
        if (file_exists($this->logPath)) {
            chmod($this->logPath, 0644);
        }
    }

    /**
     * Логирование ошибок и предупреждений валидации
     * 
     * @param string $source Название источника (gama, germes, infoflot, volga, waterway)
     * @param array $errors Массив ошибок валидации
     * @param array $warnings Массив предупреждений валидации
     */
    public function logErrors($source, $errors, $warnings)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntries = [];
        $errorsCount = is_array($errors) ? count($errors) : 0;
        $warningsCount = is_array($warnings) ? count($warnings) : 0;
        error_log("[transfer] logErrors source={$source} errors={$errorsCount} warnings={$warningsCount}");

        // Логируем ошибки
        foreach ((array) $errors as $error) {
            $entry = $this->formatLogEntry($timestamp, $source, 'ERROR', $error);
            $logEntries[] = $entry;
        }

        // Логируем предупреждения
        foreach ((array) $warnings as $warning) {
            $entry = $this->formatLogEntry($timestamp, $source, 'WARNING', $warning);
            $logEntries[] = $entry;
        }

        // Убираем пустые записи, если форматирование не удалось
        $logEntries = array_filter($logEntries, static function ($entry) {
            return trim((string) $entry) !== '';
        });

        // Записываем в файл
        if (!empty($logEntries)) {
            $content = implode("\n", $logEntries) . "\n\n";
            // Записываем в файл (FILE_APPEND добавляет к существующему содержимому)
            $bytes = file_put_contents($this->logPath, $content, FILE_APPEND | LOCK_EX);
            if ($bytes === false) {
                error_log("[transfer] logErrors failed to write to {$this->logPath}");
            } else {
                error_log("[transfer] logErrors wrote {$bytes} bytes to {$this->logPath}");
            }
        } else {
            error_log("[transfer] logErrors skipped write: no entries after formatting");
        }
    }

    /**
     * Форматирование записи лога
     * 
     * @param string $timestamp Временная метка
     * @param string $source Название источника
     * @param string $type Тип (ERROR или WARNING)
     * @param array $issue Массив с информацией об ошибке/предупреждении
     * @return string Отформатированная строка для лога
     */
    protected function formatLogEntry($timestamp, $source, $type, $issue)
    {
        $issue = is_array($issue) ? $issue : ['message' => (string) $issue];
        $context = isset($issue['context']) && is_array($issue['context']) ? $issue['context'] : [];
        $message = isset($issue['message']) && $issue['message'] !== '' ? $issue['message'] : 'Неизвестная ошибка';

        if ($message === 'Неизвестная ошибка') {
            $context['raw_issue'] = $issue;
        }

        $lines = [];
        $lines[] = "[{$timestamp}] [{$source}] {$type}: {$message}";
        
        // Добавляем контекст, если есть
        if (!empty($context)) {
            if (isset($context['count'])) {
                $lines[] = "  Количество: {$context['count']}";
            }
            
            if (isset($context['cruise_ids']) && is_array($context['cruise_ids'])) {
                $ids = $context['cruise_ids'];
                if (count($ids) <= 20) {
                    $lines[] = "  ID круизов: " . implode(', ', $ids);
                } else {
                    $lines[] = "  ID круизов (первые 20): " . implode(', ', array_slice($ids, 0, 20)) . " ... (всего " . count($ids) . ")";
                }
            }
            
            if (isset($context['json_error'])) {
                $lines[] = "  Ошибка JSON: {$context['json_error']}";
            }
            
            if (isset($context['sql'])) {
                $lines[] = "  SQL: {$context['sql']}";
            }
            
            // Добавляем другие поля контекста, если есть
            foreach ($context as $key => $value) {
                if (!in_array($key, ['count', 'cruise_ids', 'json_error', 'sql', 'params'])) {
                    if (is_array($value)) {
                        $lines[] = "  {$key}: " . json_encode($value, JSON_UNESCAPED_UNICODE);
                    } else {
                        $lines[] = "  {$key}: {$value}";
                    }
                }
            }
        }
        
        return implode("\n", $lines);
    }

    /**
     * Получить путь к файлу лога
     * 
     * @return string
     */
    public function getLogPath()
    {
        return $this->logPath;
    }
}
