<?php namespace Zen\Worker\Console\gama;

class GamaHelper
{
    /**
     * Извлечение параметра из массива Gama (поддержка @attributes)
     */
    public static function getGamaParam($arr, $param_name)
    {
        if (isset($arr['@attributes'][$param_name])) {
            return trim($arr['@attributes'][$param_name]);
        }
        if (isset($arr[$param_name])) {
            return trim($arr[$param_name]);
        }
        return false;
    }

    /**
     * Проверка валидности данных круиза
     */
    public static function validateCruiseData($cruiseData)
    {
        if (!isset($cruiseData['info']) || !isset($cruiseData['details'])) {
            return false;
        }

        $info = $cruiseData['info'];
        if (empty($info['gama_ship_id']) || empty($info['gama_ship_name'])) {
            return false;
        }

        $details = $cruiseData['details'];
        if (!isset($details['path']['point'])) {
            return false;
        }

        return true;
    }

    /**
     * Форматирование времени стоянки
     */
    public static function formatStayTime($startTime, $endTime)
    {
        $start = \Carbon\Carbon::parse($startTime);
        $end = \Carbon\Carbon::parse($endTime);
        $diffInDays = $end->diffInDays($start);
        $stay = $end->diffInSeconds($start);
        $stay = gmdate('H:i', $stay);

        return [
            'stay' => $stay,
            'diff_in_days' => $diffInDays
        ];
    }

    /**
     * Создание составного ID каюты
     */
    public static function createCabinId($categoryName, $categoryId)
    {
        return $categoryName . '|' . $categoryId;
    }

    /**
     * Разбор составного ID каюты
     */
    public static function parseCabinId($cabinId)
    {
        $parts = explode('|', $cabinId);
        return [
            'name' => $parts[0] ?? '',
            'id' => $parts[1] ?? ''
        ];
    }

    /**
     * Проверка наличия обязательных полей в данных круиза
     */
    public static function checkRequiredFields($data, $fields)
    {
        foreach ($fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Безопасное извлечение вложенных данных
     */
    public static function safeGet($array, $key, $default = null)
    {
        if (is_array($array) && array_key_exists($key, $array)) {
            return $array[$key];
        }
        return $default;
    }

    /**
     * Нормализация названия города
     */
    public static function normalizeCityName($cityName)
    {
        return trim(preg_replace('/\s+/', ' ', $cityName));
    }

    /**
     * Проверка корректности даты
     */
    public static function isValidDate($date)
    {
        try {
            \Carbon\Carbon::parse($date);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Логирование ошибок парсинга
     */
    public static function logError($message, $context = [])
    {
        \Zen\Worker\Classes\ProcessLog::add("Gama Parser Error: $message " . json_encode($context));
    }

    /**
     * Получение минимальной цены из массива
     */
    public static function getMinPrice($prices)
    {
        $validPrices = array_filter($prices, function($price) {
            return is_numeric($price) && $price > 0;
        });

        return empty($validPrices) ? 0 : min($validPrices);
    }
}
