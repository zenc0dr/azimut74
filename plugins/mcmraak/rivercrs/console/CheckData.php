<?php namespace Mcmraak\Rivercrs\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckData extends Command
{
    protected $name = 'rivercrs:check_data';
    protected $description = 'Очистить HTML-контент от ссылок и лишних тегов';

    public function handle()
    {
        $log_file = storage_path('logs/rivercrs_data_cleanup.log');

        DB::table('mcmraak_rivercrs_checkins')
            ->orderBy('id')
            ->chunk(300, function ($records) use ($log_file) {
                foreach ($records as $record) {
                    if (is_null($record->desc_1) && is_null($record->desc_2)) {
                        continue;
                    }

                    $updated_data = array_merge(
                        $this->handleDesc($record, 1),
                        $this->handleDesc($record, 2)
                    );

                    if (empty($updated_data)) {
                        continue;
                    }

                    try {
                        DB::table('mcmraak_rivercrs_checkins')
                            ->where('id', $record->id)
                            ->update($updated_data);

                        $timestamp = date('Y-m-d H:i:s');
                        $log_message = "[$timestamp] [INFO] Запись id: {$record->id} - Очистка произведена" . PHP_EOL;
                        file_put_contents($log_file, $log_message, FILE_APPEND);
                        $this->info("Запись id: {$record->id} - Очистка произведена");

                    } catch (\Exception $e) {
                        $timestamp = date('Y-m-d H:i:s');
                        $error_message = $e->getMessage();
                        $log_message = "[$timestamp] [ERROR] Ошибка записи id: {$record->id} - $error_message" . PHP_EOL;
                        file_put_contents($log_file, $log_message, FILE_APPEND);
                        $this->error("Ошибка записи id: {$record->id} - $error_message");
                    }
                }
            });

        $this->info('Обработка завершена. Логи находятся по адресу: ' . $log_file);
    }

    private function handleDesc($record, int $desc_num): array
    {
        $desc_field = "desc_$desc_num";
        $desc_value = $record->$desc_field;

        if (is_null($desc_value)) {
            return [];
        }

        $cleaned_desc = $this->clean_html($desc_value);

        if ($cleaned_desc !== $desc_value) {
            return [$desc_field => $cleaned_desc];
        }

        return [];
    }

    private function clean_html(?string $desc): ?string
    {
        if (is_null($desc)) {
            return $desc;
        }

        $desc = preg_replace('/<a\b[^>]*>(.*?)<\/a>/i', '$1', $desc);
        $desc = preg_replace('/<span\b[^>]*>(.*?)<\/span>/i', '$1', $desc);
        $desc = preg_replace('/<u\b[^>]*>(.*?)<\/u>/i', '$1', $desc);

        return $desc;
    }
}
