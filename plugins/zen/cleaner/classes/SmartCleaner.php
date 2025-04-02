<?php

namespace Zen\Cleaner\Classes;

use DB;

class SmartCleaner
{
    private bool $test_mode = false;

    private string $uploads_path;
    private array $files_list;
    private ?array $themes_links = null;
    private int $removed_files_count = 0;
    private int $removed_records_count = 0;
    private int $clean_size = 0;
    private array $exists_files = [];

    public function __construct()
    {
        $this->uploads_path = storage_path('app/uploads/public');
    }

    public function run()
    {
        $this->output("Сканирование $this->uploads_path");
        $this->makeFilesList();
        $files_count = count($this->files_list);
        $this->output("Обнаружено файлов: $files_count");
        $this->filesHandle();
        $this->dbHandle();
        $this->removeEmptyDirs();
        $removed_size = $this->formatSizeUnits($this->clean_size);
        $this->output(
            join(
                PHP_EOL,
                [
                    "Обработано файлов: $files_count",
                    "Удалено: $this->removed_files_count",
                    "Очищено: $removed_size",
                    "Удалено записей в БД: $this->removed_records_count"
                ]
            )
        );
    }

    /**
     * Получить список файлов
     */
    private function makeFilesList(): void
    {
        $this->files_list = $this->getAllFiles($this->uploads_path);
    }

    /**
     * Получить все файлы
     * @param $dir
     * @return array
     */
    private function getAllFiles($dir): array
    {
        $files = [];
        # Открываем директорию
        if ($handle = opendir($dir)) {
            # Читаем содержимое директории
            while (false !== ($entry = readdir($handle))) {
                # Пропускаем текущую и родительскую директории
                if ($entry != "." && $entry != "..") {
                    $path = $dir . DIRECTORY_SEPARATOR . $entry;
                    # Если это директория, рекурсивно вызываем функцию
                    if (is_dir($path)) {
                        $files = array_merge($files, $this->getAllFiles($path));
                    } else {
                        # Если это файл, добавляем его в массив
                        $files[] = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path);
                    }
                }
            }
            closedir($handle);
        }

        return $files;
    }

    /**
     * Вывод сообщений в консоль
     * @param string $message
     * @param string $separator
     */
    private function output(string $message, $separator = PHP_EOL)
    {
        echo $message . $separator;
    }

    /**
     * Удаление пустых папок
     */
    private function removeEmptyDirs(): void
    {
        $this->output('Удаление пустых папок...', ' ');
        shell_exec("find $this->uploads_path -type d -empty -delete");
        $this->output('OK');
    }

    /**
     * Обработчик файлов
     */
    private function filesHandle(): void
    {
        foreach ($this->files_list as $file_path) {
            $file_must_be_unlink = false;
            $disk_name = basename($file_path);
            $grinder_original = null;

            # Это файл обработанный Grinder
            $is_grinder_file = strpos($disk_name, '_grinder_') !== false;

            if ($is_grinder_file) {
                $grinder_original = $this->grindersOriginalName($disk_name);
            }

            # Файл существует в базе
            $system_file = DB::table('system_files')
                ->where('disk_name', $grinder_original ?? $disk_name)
                ->first();

            # Определяем файл без аттача
            $file_without_attach = !$system_file || !$system_file->attachment_type;

            # Файл без аттача не найден в темах - удалить
            if ($file_without_attach) {
                if (!in_array($file_path, $this->linksInThemes())) {
                    $file_must_be_unlink = true;
                }
            } else {
                # Если класс не существует то удалить
                if (!class_exists($system_file->attachment_type)) {
                    $file_must_be_unlink = true;
                # Если класс существует то проверить экземпляр модели
                } else {
                    $attachment_id = intval($system_file->attachment_id);
                    $model = app($system_file->attachment_type)->find($attachment_id);
                    # Если экземпляра модели не существует удалить
                    if (!$model) {
                        $file_must_be_unlink = true;
                    }
                }
            }

            if ($file_must_be_unlink) {
                if ($is_grinder_file) {
                    $dir_path = dirname($file_path);
                    $this->removeFile("$dir_path/$grinder_original");
                }
                $this->removeFile($file_path);
            } else {
                $this->exists_files[] = $disk_name;
            }
        }
    }

    /**
     * Удалить записи о файле из БД
     */
    private function dbHandle(): void
    {
        DB::table('system_files')
            ->orderBy('id')
            ->chunk(100, function ($files) {
                foreach ($files as $file) {
                    if (!in_array($file->disk_name, $this->exists_files)) {
                        $this->removeFileRecord($file);
                    }
                }
            });
    }

    /**
     * Удаляет файл с выводом в логи
     * @param string $file_path
     */
    private function removeFile(string $file_path): void
    {
        if (file_exists($file_path)) {
            $this->clean_size += filesize($file_path);
            if ($this->test_mode) {
                file_put_contents(
                    storage_path('files_to_remove.txt'),
                    $file_path . PHP_EOL,
                    FILE_APPEND
                );
            } else {
                unlink($file_path);
            }
            $this->output("Удалён файл: $file_path");
            $this->removed_files_count++;
        }
    }

    /**
     * Удаление записи из БД
     * @param int $file_id
     */
    private function removeFileRecord(Object $file): void
    {
        if (!$this->test_mode) {
            DB::table('system_files')
                ->where('id', $file->id)
                ->delete();
        }
        $this->output("Удалена запись о файле: [$file->id] $file->disk_name");
        $this->removed_records_count++;
    }

    /**
     * Получает имя файла оригинала (не сжатого grinder)
     * @param string $grinders_name
     * @return string
     */
    private function grindersOriginalName(string $grinders_name): string
    {
        return preg_replace('/_grinder_.*(\.\w+)$/', '$1', $grinders_name);
    }

    /**
     * Возвращает массив ссылок найденных в темах
     * @return array
     */
    private function linksInThemes(): array
    {
        if ($this->themes_links) {
            return $this->themes_links;
        }

        $links = array_merge(
            $this->findImageLinks(base_path('themes/azimut-tur')),
            $this->findImageLinks(base_path('themes/azimut-tur-pro')),
        );

        $links = array_unique($links);
        return $this->themes_links = array_values($links);
    }

    /**
     * Находит ссылки сканируя папки
     * @param $directory
     * @param array $links
     * @return array
     */
    private function findImageLinks($directory, &$links = []): array
    {
        # Сканируем директорию
        $files = scandir($directory);

        # Перебираем все файлы и папки
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $full_path = $directory . '/' . $file;

            # Если это директория, вызываем рекурсивно этот же метод
            if (is_dir($full_path)) {
                $this->findImageLinks($full_path, $links);
            }

            # Если это файл, проверяем его содержимое
            if (is_file($full_path)) {
                $extension = pathinfo($full_path, PATHINFO_EXTENSION);

                if (!in_array($extension, ['htm', 'html', 'php'])) {
                    continue;
                }

                # Читаем содержимое файла
                $content = file_get_contents($full_path);

                # Регулярное выражение для поиска ссылок
                $pattern = '#/storage/app/uploads/public/[a-zA-Z0-9/_\-]+/\w+\.(jpg|jpeg|gif|png|webp)#i';

                # Ищем все совпадения
                if (preg_match_all($pattern, $content, $matches)) {
                    // Добавляем найденные ссылки в общий массив
                    $links = array_merge($links, $matches[0]);
                }
            }
        }

        return $links;
    }

    /**
     * #kriti
     * Форматирует вывод размера файла
     * @param $bytes
     * @return string
     */
    private function formatSizeUnits($bytes): string
    {
        $units = ['bytes', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        $formattedSize = $bytes / pow(1024, $factor);

        return number_format($formattedSize, 2) . ' ' . $units[$factor];
    }
}
