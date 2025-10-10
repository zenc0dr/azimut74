<?php namespace Zen\Master\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use DB;
use Config;
use Storage;

class DatabaseDump extends Command
{
    protected $name = 'master:dbdump';

    protected $description = 'Создать дамп базы данных и отправить в Telegram';

    public function handle()
    {
        $this->cliOut('🚀 Запуск создания дампа базы данных...');
        
        try {
            // Получаем параметры подключения к БД
            $connection = $this->getConnection();
            
            // Создаем дамп
            $filename = $this->createDump($connection);
            
            if (!$filename) {
                $this->error('❌ Ошибка создания дампа базы данных');
                return;
            }
            
            $this->cliOut("✅ Дамп создан: $filename");
            
            // Отправляем в Telegram
            $this->sendToTelegram($filename);
            
            // Удаляем временный файл
            $this->cleanup($filename);
            
            $this->cliOut('🎉 Дамп базы данных успешно создан и отправлен в Telegram');
            
        } catch (\Exception $e) {
            $this->error('❌ Ошибка: ' . $e->getMessage());
        }
    }

    /**
     * Получить параметры подключения к БД
     */
    private function getConnection(): array
    {
        $connection_name = config('database.default');
        $connection = config("database.connections.$connection_name");
        
        return [
            'db_host' => $connection['host'],
            'db_port' => $connection['port'],
            'db_name' => $connection['database'],
            'db_user' => $connection['username'],
            'db_pass' => $connection['password'],
        ];
    }

    /**
     * Создать дамп базы данных
     */
    private function createDump(array $connection): ?string
    {
        $time = date('d-m-Y-H-i-s');
        $filename = "azimut74_dump_{$time}.sql.gz";
        
        // Создаем директорию для бэкапов
        $backupDir = storage_path('backups');
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $filepath = $backupDir . '/' . $filename;
        
        // Получаем список таблиц (исключаем системные и логи)
        $tables = $this->getTables($connection['db_name']);
        $tables_str = implode(' ', $tables);
        
        $this->cliOut('📊 Создание дампа через mysqldump...');
        $this->cliOut('📋 Таблиц для дампа: ' . count($tables));
        
        // Команда mysqldump с оптимизацией для быстрого восстановления
        $dump_command = [
            'mysqldump',
            '--extended-insert',           // Группировка INSERT'ов
            '--single-transaction',        // Консистентность данных
            '--routines',                  // Процедуры и функции
            '--triggers',                  // Триггеры
            '--events',                    // События
            '--hex-blob',                  // Бинарные данные в hex
            '--disable-keys',              // Отключение индексов при вставке
            '--add-drop-table',            // DROP TABLE IF EXISTS
            '--add-locks',                 // LOCK/UNLOCK TABLES
            '--quick',                     // Быстрая загрузка
            '--lock-tables=false',         // Не блокировать таблицы
            '--no-tablespaces',            // Не дампить tablespaces
            '--skip-lock-tables',          // Пропустить LOCK TABLES
            '--host=' . $connection['db_host'],
            '--port=' . $connection['db_port'],
            '--user=' . $connection['db_user'],
            '--password=' . $connection['db_pass'],
            '--protocol=TCP',
            $connection['db_name'],
            $tables_str,
            '| gzip > ' . $filepath
        ];
        
        $this->cliOut('📊 Создание дампа...');
        $this->cliOut('Команда: ' . implode(' ', $dump_command));
        
        $output = [];
        $return_code = 0;
        exec(implode(' ', $dump_command), $output, $return_code);
        
        if ($return_code !== 0) {
            $this->error('❌ Ошибка создания дампа: ' . implode("\n", $output));
            return null;
        }
        
        if (!file_exists($filepath)) {
            $this->error('❌ Файл дампа не создан');
            return null;
        }
        
        $size = filesize($filepath);
        $this->cliOut("📁 Размер дампа: " . $this->formatSize($size));
        
        return $filepath;
    }

    /**
     * Получить список таблиц для дампа
     */
    private function getTables(string $db_name): array
    {
        $tables = DB::select("
            SELECT TABLE_NAME 
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = ? 
            AND TABLE_NAME NOT LIKE '%_logs%'
            AND TABLE_NAME NOT LIKE '%_cache%'
            AND TABLE_NAME NOT LIKE '%_sessions%'
            ORDER BY TABLE_NAME
        ", [$db_name]);
        
        return array_column($tables, 'TABLE_NAME');
    }


    /**
     * Отправить дамп в Telegram
     */
    private function sendToTelegram(string $filepath): void
    {
        $this->cliOut('📤 Отправка в Telegram...');
        
        // Отправляем уведомление о начале отправки
        $message = "🗄️ <b>ДАМП БАЗЫ ДАННЫХ</b>\n\n";
        $message .= "📅 <b>Дата:</b> " . date('d.m.Y H:i:s') . "\n";
        $message .= "📁 <b>Файл:</b> " . basename($filepath) . "\n";
        $message .= "💾 <b>Размер:</b> " . $this->formatSize(filesize($filepath)) . "\n\n";
        $message .= "⏳ <b>Отправка файла...</b>";
        
        // Отправляем уведомление через скрипт
        $telegram_script = '/aum/docker/azimut74/send_tg_notes.py';
        if (file_exists($telegram_script)) {
            exec("python3 '$telegram_script' '$message'");
        }
        
        // Отправляем файл напрямую через API
        $this->sendFileToTelegram($filepath);
    }

    /**
     * Отправить файл в Telegram через API
     */
    private function sendFileToTelegram(string $filepath): void
    {
        $bot_token = "8099503137:AAGUkaYOwBmZtvltrk2WdWuf3k1rVMrmRlk";
        $chat_id = "-1003075512422";
        
        $url = "https://api.telegram.org/bot{$bot_token}/sendDocument";
        
        $post_fields = [
            'chat_id' => $chat_id,
            'caption' => "🗄️ <b>Дамп базы данных azimut74</b>\n📅 " . date('d.m.Y H:i:s'),
            'parse_mode' => 'HTML'
        ];
        
        $post_fields['document'] = new \CURLFile($filepath, 'application/gzip', basename($filepath));
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 минут на загрузку
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: multipart/form-data'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            $this->cliOut('✅ Файл успешно отправлен в Telegram');
        } else {
            $this->error('❌ Ошибка отправки файла в Telegram: HTTP ' . $http_code);
        }
    }

    /**
     * Очистка временных файлов
     */
    private function cleanup(string $filepath): void
    {
        if (file_exists($filepath)) {
            unlink($filepath);
            $this->cliOut('🗑️ Временный файл удален');
        }
    }

    /**
     * Форматирование размера файла
     */
    private function formatSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }

    /**
     * Вывод в консоль с временной меткой
     */
    private function cliOut(string $message): void
    {
        $time = date('d.m.Y H:i:s');
        $this->output->writeln("[$time] $message");
    }

    protected function getOptions()
    {
        return [
            ['tables', 't', InputOption::VALUE_OPTIONAL, 'Список таблиц для дампа (через запятую)', null],
            ['exclude', 'e', InputOption::VALUE_OPTIONAL, 'Таблицы для исключения (через запятую)', null],
        ];
    }
}
