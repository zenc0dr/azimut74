<?php namespace Zen\Worker\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use File;

class ClearCache extends Command
{
    protected $name = 'worker:clear-cache';
    protected $description = 'Очистка файлового кеша парсеров';

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        $cacheDirs = [
            'gama_cache' => 'Gama',
            'germes_cache' => 'Germes',
            'infoflot_cache' => 'Infoflot',
            'waterway_cache' => 'Waterway'
        ];

        $totalFiles = 0;
        $totalSize = 0;

        foreach ($cacheDirs as $dirName => $parserName) {
            $cachePath = storage_path($dirName);

            if (!is_dir($cachePath)) {
                $this->info("Папка $parserName ($dirName) не существует, пропускаем");
                continue;
            }

            $stats = $this->getDirectoryStats($cachePath);
            $filesCount = $stats['files'];
            $size = $stats['size'];

            if ($filesCount == 0) {
                $this->info("Папка $parserName ($dirName) пуста");
                continue;
            }

            $this->info("Очистка кеша $parserName ($dirName)...");
            $this->info("  Файлов: $filesCount");
            $this->info("  Размер: " . $this->formatSizeUnits($size));

            try {
                File::deleteDirectory($cachePath);
                $this->info("  ✓ Кеш $parserName успешно очищен");

                $totalFiles += $filesCount;
                $totalSize += $size;
            } catch (\Exception $e) {
                $this->error("  ✗ Ошибка при очистке кеша $parserName: " . $e->getMessage());
            }
        }

        if ($totalFiles > 0) {
            $this->info("");
            $this->info("Итого удалено:");
            $this->info("  Файлов: $totalFiles");
            $this->info("  Освобождено места: " . $this->formatSizeUnits($totalSize));
        } else {
            $this->info("Нет файлов для удаления");
        }
    }

    /**
     * Получить статистику директории
     */
    protected function getDirectoryStats($directory)
    {
        $files = 0;
        $size = 0;

        if (!is_dir($directory)) {
            return ['files' => 0, 'size' => 0];
        }

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $files++;
                    $size += $file->getSize();
                }
            }
        } catch (\Exception $e) {
            // Игнорируем ошибки доступа к файлам
        }

        return ['files' => $files, 'size' => $size];
    }

    /**
     * Форматировать размер в читаемый вид
     */
    protected function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            return $bytes . ' bytes';
        } elseif ($bytes == 1) {
            return $bytes . ' byte';
        } else {
            return '0 bytes';
        }
    }

    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     * @return array
     */
    protected function getOptions()
    {
        return [];
    }
}

