<?php namespace Zen\Worker\Classes;

use Throwable;

set_time_limit(0);

class Convertor
{
    /**
     * Преобразует XML строку в массив
     * @param string $xml XML строка для преобразования
     * @param int $timeout Таймаут выполнения в секундах (по умолчанию 60 секунд)
     * @return array|null Возвращает массив или null при ошибке
     */
    public static function xmlToArr(string $xml, int $timeout = 60): ?array
    {
        try {
            // Устанавливаем таймаут для этой операции
            $originalTimeout = ini_get('max_execution_time');
            set_time_limit($timeout);

            // Проверка входных данных
            if (empty($xml) || !is_string($xml)) {
                ProcessLog::add("Convertor::xmlToArr - Получены некорректные входные данные");
                return null;
            }

            // Отключаем уведомления при обработке XML и устанавливаем лимит памяти
            libxml_use_internal_errors(true);
            $memoryLimit = ini_get('memory_limit');

            // Загружаем XML с опциями для большей безопасности
            $options = LIBXML_NONET | LIBXML_NOCDATA | LIBXML_COMPACT;

            // Устанавливаем таймаут для внутренних операций libxml если доступно
            if (defined('LIBXML_PARSEHUGE')) {
                $options |= LIBXML_PARSEHUGE;
            }

            $xmlObj = simplexml_load_string($xml, 'SimpleXMLElement', $options);

            // Проверяем на успешную загрузку
            if ($xmlObj === false) {
                $errors = libxml_get_errors();
                $errorMsg = "Ошибка при загрузке XML: ";
                foreach ($errors as $error) {
                    $errorMsg .= $error->message . " (строка {$error->line}) ";
                }
                ProcessLog::add($errorMsg);
                libxml_clear_errors();
                return null;
            }

            // Преобразуем в JSON и затем в массив
            $json = json_encode($xmlObj, JSON_UNESCAPED_UNICODE);
            $result = json_decode($json, true);

            // Проверяем результат преобразования JSON
            if (json_last_error() !== JSON_ERROR_NONE) {
                ProcessLog::add("Convertor::xmlToArr - Ошибка преобразования JSON: " . json_last_error_msg());
                return null;
            }

            // Восстанавливаем исходный таймаут
            if ($originalTimeout > 0) {
                set_time_limit($originalTimeout);
            }

            return $result;
        } catch (Throwable $e) {
            // Перехватываем все типы исключений и ошибок (Throwable охватывает Exception и Error)
            ProcessLog::add("Convertor::xmlToArr - Критическая ошибка: " . $e->getMessage() .
                " в файле " . $e->getFile() . " на строке " . $e->getLine());

            // Восстанавливаем исходный таймаут в случае исключения
            if (isset($originalTimeout) && $originalTimeout > 0) {
                set_time_limit($originalTimeout);
            }

            return null;
        }
    }
}
