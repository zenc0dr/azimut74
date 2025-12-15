<?php namespace Zen\Worker\ReportWidgets;

use Backend\Classes\ReportWidgetBase;
use Zen\Worker\Classes\Core;
use Flash;
use October\Rain\Exception\ApplicationException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class CacheStatsWidget extends ReportWidgetBase
{
    protected $defaultAlias = 'cache_stats';

    public function render()
    {
        $this->vars['cacheStats'] = $this->getCacheStats();
        
        return $this->makePartial('widget');
    }

    public function defineProperties()
    {
        return [
            'title' => [
                'title'             => 'backend::lang.dashboard.widget_title_label',
                'default'           => 'Статистика кеша парсеров',
                'type'              => 'string',
                'validationPattern' => '^.+$',
                'validationMessage' => 'backend::lang.dashboard.widget_title_error',
            ]
        ];
    }

    /**
     * Получить статистику кеша для всех парсеров
     */
    protected function getCacheStats()
    {
        $cacheDirs = [
            'gama_cache' => 'Gama',
            'germes_cache' => 'Germes',
            'parsers_cache/infoflot' => 'Infoflot',
            'parsers_cache/volga' => 'Volga',
            'parsers_cache/waterway' => 'Waterway'
        ];

        $stats = [];

        foreach ($cacheDirs as $dirName => $parserName) {
            $cachePath = storage_path($dirName);
            
            if (!is_dir($cachePath)) {
                $stats[] = [
                    'name' => $parserName,
                    'path' => $dirName,
                    'files' => 0,
                    'size' => 0,
                    'sizeFormatted' => '0 bytes',
                    'lastModified' => '-',
                    'exists' => false
                ];
                continue;
            }

            $dirStats = $this->getDirectoryStats($cachePath);
            
            $lastModified = $dirStats['lastModified'] ? date('d.m.Y H:i', $dirStats['lastModified']) : '-';
            
            $stats[] = [
                'name' => $parserName,
                'path' => $dirName,
                'files' => $dirStats['files'],
                'size' => $dirStats['size'],
                'sizeFormatted' => $this->formatSizeUnits($dirStats['size']),
                'lastModified' => $lastModified,
                'exists' => true
            ];
        }

        return $stats;
    }

    /**
     * Получить статистику директории
     */
    protected function getDirectoryStats($directory)
    {
        $files = 0;
        $size = 0;
        $lastModified = 0;

        if (!is_dir($directory)) {
            return ['files' => 0, 'size' => 0, 'lastModified' => 0];
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
                    $fileMTime = $file->getMTime();
                    if ($fileMTime > $lastModified) {
                        $lastModified = $fileMTime;
                    }
                }
            }
        } catch (\Exception $e) {
            // Игнорируем ошибки доступа к файлам
        }

        return ['files' => $files, 'size' => $size, 'lastModified' => $lastModified];
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
     * Обработчик кнопки очистки кеша
     */
    public function onClearCache()
    {
        // Всегда устанавливаем переменную перед рендерингом
        $this->vars['cacheStats'] = $this->getCacheStats();
        
        try {
            $core = new Core();
            $core->artisanExec('worker:clear-cache');
            
            Flash::success('Команда очистки кеша запущена в фоновом режиме');
            
            // Обновляем статистику после очистки
            $this->vars['cacheStats'] = $this->getCacheStats();
            
            return [
                'partial' => $this->makePartial('widget')
            ];
        } catch (ApplicationException $ex) {
            Flash::error('Ошибка при запуске команды: ' . $ex->getMessage());
            
            return [
                'error' => $ex->getMessage(),
                'partial' => $this->makePartial('widget')
            ];
        } catch (\Exception $ex) {
            Flash::error('Ошибка при запуске команды: ' . $ex->getMessage());
            
            return [
                'error' => $ex->getMessage(),
                'partial' => $this->makePartial('widget')
            ];
        }
    }
}

