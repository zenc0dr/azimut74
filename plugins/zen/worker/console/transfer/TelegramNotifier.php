<?php namespace Zen\Worker\Console\transfer;

use Exception;

/**
 * Класс для отправки уведомлений в Telegram
 */
class TelegramNotifier
{
    /**
     * Telegram Bot Token
     */
    private $botToken = '8599659495:AAHbFqy8QFOnIVehQLiqmDvIUChIR847NT4';
    
    /**
     * Chat ID для отправки сообщений
     * Читается из переменной окружения AXIS_BOT_CHAT_ID
     */
    private $chatId;
    
    /**
     * Базовый URL API Telegram
     */
    private $apiUrl = 'https://api.telegram.org/bot';
    
    /**
     * ID сообщения для обновления
     */
    private $messageId = null;
    
    /**
     * Предыдущий текст сообщения (для проверки изменений)
     */
    private $lastMessageText = null;
    
    /**
     * Конструктор - инициализирует chat_id из переменной окружения
     */
    public function __construct()
    {
        $this->chatId = env('AXIS_BOT_CHAT_ID', '-1002538310668');
        
        if (empty($this->chatId)) {
            throw new Exception('AXIS_BOT_CHAT_ID не задан в .env файле');
        }
    }
    
    /**
     * Отправка сообщения в Telegram
     * 
     * @param string $message Текст сообщения
     * @param string $parseMode Режим парсинга (HTML, Markdown)
     * @return bool true если успешно, false при ошибке
     */
    public function sendMessage($message, $parseMode = 'HTML')
    {
        try {
            $url = $this->apiUrl . $this->botToken . '/sendMessage';
            $params = [
                'chat_id' => $this->chatId,
                'text' => $message,
                'parse_mode' => $parseMode
            ];
            
            $queryString = http_build_query($params);
            $fullUrl = $url . '?' . $queryString;
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $fullUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                // Логируем ошибку, но не прерываем выполнение
                error_log("Telegram API error: " . $error);
                return false;
            }
            
            if ($httpCode !== 200) {
                error_log("Telegram API HTTP error: $httpCode, response: " . $response);
                return false;
            }
            
            $result = json_decode($response, true);
            if (isset($result['ok']) && $result['ok'] === true) {
                return true;
            }
            
            error_log("Telegram API error: " . ($result['description'] ?? 'Unknown error'));
            return false;
            
        } catch (Exception $e) {
            error_log("Telegram notification exception: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Отправка сообщения о начале обработки источника
     * 
     * @param string $sourceName Название источника
     * @return bool
     */
    public function notifyStart($sourceName)
    {
        $message = "🔄 <b>Начало обработки источника: {$sourceName}</b>";
        return $this->sendMessage($message);
    }
    
    /**
     * Отправка сообщения о завершении обработки источника со статистикой
     * 
     * @param string $sourceName Название источника
     * @param array $stats Статистика обработки
     * @return bool
     */
    public function notifyComplete($sourceName, $stats)
    {
        $ships = $stats['ships'] ?? 0;
        $cabinCategories = $stats['cabin_categories'] ?? 0;
        $cruises = $stats['cruises'] ?? 0;
        
        $message = "✅ <b>Обработка источника {$sourceName} завершена</b>\n\n";
        $message .= "📊 <b>Статистика:</b>\n";
        $message .= "🚢 Обработано теплоходов: <b>{$ships}</b>\n";
        $message .= "🏠 Обработано категорий кают: <b>{$cabinCategories}</b>\n";
        $message .= "🎫 Обработано круизов: <b>{$cruises}</b>";
        
        return $this->sendMessage($message);
    }
    
    /**
     * Отправка сообщения об ошибке
     * 
     * @param string $sourceName Название источника
     * @param string $errorMessage Сообщение об ошибке
     * @return bool
     */
    public function notifyError($sourceName, $errorMessage)
    {
        $message = "❌ <b>Ошибка обработки источника: {$sourceName}</b>\n\n";
        $message .= "Ошибка: " . htmlspecialchars($errorMessage);
        return $this->sendMessage($message);
    }
    
    /**
     * Создание или обновление единого сообщения о прогрессе
     * 
     * @param array $sourcesData Массив данных о источниках ['sourceKey' => ['name' => ..., 'status' => ..., 'stats' => ...]]
     * @param string $currentSource Текущий обрабатываемый источник (опционально)
     * @return bool
     */
    public function updateProgress($sourcesData, $currentSource = null)
    {
        $message = "🔄 <b>Импорт данных из SQLite в MySQL</b>\n\n";
        
        $totalShips = 0;
        $totalCabinCategories = 0;
        $totalCruises = 0;
        $completed = 0;
        $errors = 0;
        
        foreach ($sourcesData as $sourceKey => $data) {
            $name = $data['name'] ?? $sourceKey;
            $status = $data['status'] ?? 'pending';
            $stats = $data['stats'] ?? [];
            
            // Определяем иконку статуса
            $icon = '⏳'; // pending
            if ($status === 'processing') {
                $icon = '🔄';
            } elseif ($status === 'success') {
                $icon = '✅';
                $completed++;
            } elseif ($status === 'error' || $status === 'validation_failed') {
                $icon = '❌';
                $errors++;
            }
            
            $message .= "{$icon} <b>{$name}</b>";
            
            if ($status === 'processing') {
                $message .= " (обработка...)";
            } elseif ($status === 'success' && !empty($stats)) {
                $ships = $stats['ships'] ?? 0;
                $cabinCategories = $stats['cabin_categories'] ?? 0;
                $cruises = $stats['cruises'] ?? 0;
                
                $totalShips += $ships;
                $totalCabinCategories += $cabinCategories;
                $totalCruises += $cruises;
                
                $message .= "\n   🚢 {$ships} | 🏠 {$cabinCategories} | 🎫 {$cruises}";
            } elseif ($status === 'error' || $status === 'validation_failed') {
                $errorMsg = $data['error'] ?? 'Ошибка обработки';
                $message .= "\n   ⚠️ " . htmlspecialchars(mb_substr($errorMsg, 0, 100));
            }
            
            $message .= "\n";
        }
        
        // Итоговая статистика
        if ($completed > 0 || $totalShips > 0 || $totalCabinCategories > 0 || $totalCruises > 0) {
            $message .= "\n📊 <b>Итого:</b>\n";
            $message .= "🚢 Теплоходов: <b>{$totalShips}</b>\n";
            $message .= "🏠 Категорий кают: <b>{$totalCabinCategories}</b>\n";
            $message .= "🎫 Круизов: <b>{$totalCruises}</b>\n";
            $message .= "✅ Завершено: <b>{$completed}</b>";
            if ($errors > 0) {
                $message .= " | ❌ Ошибок: <b>{$errors}</b>";
            }
        }
        
        // Проверяем, изменилось ли содержимое сообщения
        if ($this->lastMessageText === $message && $this->messageId !== null) {
            // Сообщение не изменилось, не обновляем
            return true;
        }
        
        // Отправляем или обновляем сообщение
        if ($this->messageId === null) {
            // Первая отправка
            $result = $this->sendOrUpdateMessage($message, true);
        } else {
            // Обновление существующего сообщения
            $result = $this->sendOrUpdateMessage($message, false);
        }
        
        // Сохраняем текст сообщения при успешной отправке/обновлении
        if ($result) {
            $this->lastMessageText = $message;
        }
        
        return $result;
    }
    
    /**
     * Отправка или обновление сообщения
     * 
     * @param string $message Текст сообщения
     * @param bool $isNew Новое сообщение или обновление существующего
     * @return bool
     */
    private function sendOrUpdateMessage($message, $isNew = true)
    {
        try {
            if ($isNew || $this->messageId === null) {
                // Отправляем новое сообщение
                $url = $this->apiUrl . $this->botToken . '/sendMessage';
                $params = [
                    'chat_id' => $this->chatId,
                    'text' => $message,
                    'parse_mode' => 'HTML'
                ];
            } else {
                // Обновляем существующее сообщение
                $url = $this->apiUrl . $this->botToken . '/editMessageText';
                $params = [
                    'chat_id' => $this->chatId,
                    'message_id' => $this->messageId,
                    'text' => $message,
                    'parse_mode' => 'HTML'
                ];
            }
            
            $queryString = http_build_query($params);
            $fullUrl = $url . '?' . $queryString;
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $fullUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                error_log("Telegram API error: " . $error);
                return false;
            }
            
            if ($httpCode !== 200) {
                error_log("Telegram API HTTP error: $httpCode, response: " . $response);
                return false;
            }
            
            $result = json_decode($response, true);
            if (isset($result['ok']) && $result['ok'] === true) {
                // Сохраняем message_id при первой отправке
                if ($isNew && isset($result['result']['message_id'])) {
                    $this->messageId = $result['result']['message_id'];
                }
                return true;
            }
            
            // Если сообщение не найдено (удалено), пытаемся отправить новое
            if (!$isNew && isset($result['description']) && 
                (strpos($result['description'], 'message to edit not found') !== false ||
                 strpos($result['description'], 'message can\'t be edited') !== false)) {
                // Сбрасываем message_id и отправляем новое сообщение
                $this->messageId = null;
                $this->lastMessageText = null;
                return $this->sendOrUpdateMessage($message, true);
            }
            
            // Если сообщение не изменилось - это не ошибка, просто игнорируем
            if (!$isNew && isset($result['description']) && 
                strpos($result['description'], 'message is not modified') !== false) {
                // Сообщение не изменилось, считаем успешным
                return true;
            }
            
            error_log("Telegram API error: " . ($result['description'] ?? 'Unknown error'));
            return false;
            
        } catch (Exception $e) {
            error_log("Telegram notification exception: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Сброс message_id (для нового запуска)
     */
    public function reset()
    {
        $this->messageId = null;
        $this->lastMessageText = null;
    }
}

