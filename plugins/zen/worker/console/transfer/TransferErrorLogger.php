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
        file_put_contents($this->logPath, '', LOCK_EX);
        chmod($this->logPath, 0644);
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

        // Логируем ошибки
        foreach ($errors as $error) {
            $entry = $this->formatLogEntry($timestamp, $source, 'ERROR', $error);
            $logEntries[] = $entry;
        }

        // Логируем предупреждения
        foreach ($warnings as $warning) {
            $entry = $this->formatLogEntry($timestamp, $source, 'WARNING', $warning);
            $logEntries[] = $entry;
        }

        // Записываем в файл
        if (!empty($logEntries)) {
            $content = implode("\n", $logEntries) . "\n\n";
            // Записываем в файл (FILE_APPEND добавляет к существующему содержимому)
            file_put_contents($this->logPath, $content, FILE_APPEND | LOCK_EX);
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
        $lines = [];
        $lines[] = "[{$timestamp}] [{$source}] {$type}: {$issue['message']}";
        
        // Добавляем контекст, если есть
        if (!empty($issue['context'])) {
            $context = $issue['context'];
            
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
