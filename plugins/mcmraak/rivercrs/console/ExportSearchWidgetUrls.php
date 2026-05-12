<?php namespace Mcmraak\Rivercrs\Console;

use DB;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

/**
 * Формирует CSV со страницами, где на теме azimut-tur-pro смонтированы
 * #search-widget-app (Vue SearchWidget) и виджет отзывов — см. cruises.htm и ship-cruises.htm.
 */
class ExportSearchWidgetUrls extends Command
{
    protected $name = 'rivercrs:export-search-widget-urls';

    protected $description = 'Экспорт URL страниц с поисковым виджетом RiverCRS и отзывами в CSV (storage/app)';

    protected function getOptions()
    {
        return [
            [
                'output',
                null,
                InputOption::VALUE_OPTIONAL,
                'Путь к CSV относительно storage/app',
                'rivercrs_search_widget_urls.csv',
            ],
        ];
    }

    public function handle()
    {
        $relative = $this->option('output') ?: 'rivercrs_search_widget_urls.csv';
        $relative = ltrim(str_replace(['\\', '..'], ['/', ''], $relative), '/');
        $fullPath = storage_path('app/' . $relative);

        $base = rtrim(env('APP_URL', config('app.url', '')), '/');
        $prefix = $base . '/russia-river-cruises';

        $rows = [];

        $rows[] = [
            'page_type' => 'index',
            'slug_or_id' => '',
            'title' => 'Главная раздела (URL без сегмента slug, RivercrsCore INDEX_CRUISE_ID)',
            'url' => $prefix,
        ];

        DB::table('mcmraak_rivercrs_cruises')
            ->where('active', 1)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->select(['id', 'slug', 'name'])
            ->get()
            ->each(function ($row) use (&$rows, $prefix) {
                $rows[] = [
                    'page_type' => 'cruise_menu',
                    'slug_or_id' => (string) $row->slug,
                    'title' => $row->name,
                    'url' => $prefix . '/' . $row->slug,
                ];
            });

        DB::table('mcmraak_rivercrs_transit')
            ->where('active', 1)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->select(['id', 'slug', 'name'])
            ->get()
            ->each(function ($row) use (&$rows, $prefix) {
                $rows[] = [
                    'page_type' => 'transit',
                    'slug_or_id' => (string) $row->slug,
                    'title' => $row->name,
                    'url' => $prefix . '/' . $row->slug,
                ];
            });

        DB::table('mcmraak_rivercrs_motorships')
            ->orderBy('id')
            ->select(['id', 'name'])
            ->get()
            ->each(function ($row) use (&$rows, $prefix) {
                $rows[] = [
                    'page_type' => 'motorship',
                    'slug_or_id' => (string) $row->id,
                    'title' => $row->name,
                    'url' => $prefix . '/motorship/' . $row->id,
                ];
            });

        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $fh = fopen($fullPath, 'wb');
        if ($fh === false) {
            $this->error('Не удалось открыть файл: ' . $fullPath);
            return 1;
        }

        fwrite($fh, "\xEF\xBB\xBF");
        fputcsv($fh, ['page_type', 'slug_or_id', 'title', 'url'], ';');

        foreach ($rows as $r) {
            fputcsv($fh, [
                $r['page_type'],
                $r['slug_or_id'],
                $r['title'],
                $r['url'],
            ], ';');
        }

        fclose($fh);

        $this->info('Записано строк: ' . count($rows));
        $this->info($fullPath);

        return 0;
    }
}
