<?php namespace Mcmraak\Rivercrs\Console;

use Illuminate\Console\Command;
use Mcmraak\Rivercrs\Classes\ReviewsDistributionAudit;
use Mcmraak\Rivercrs\Classes\ReviewsDistributionCsv;
use Symfony\Component\Console\Input\InputOption;

/**
 * Отчёт для клиента: фактическое распределение отзывов vs эталонный CSV.
 */
class ReportReviewDistribution extends Command
{
    protected $name = 'rivercrs:report-review-distribution';

    protected $description = 'CSV-отчёт о привязках отзывов по эталонному Distribution_of_reviews.csv';

    /** @var ReviewsDistributionCsv */
    private $csv;

    protected function getOptions()
    {
        return [
            [
                'csv',
                null,
                InputOption::VALUE_OPTIONAL,
                'Эталонный CSV (как у rivercrs:distribute-reviews)',
                'storage/app/distribution_of_reviews.csv',
            ],
            [
                'output',
                null,
                InputOption::VALUE_OPTIONAL,
                'Путь к отчёту относительно storage/app',
                'reviews_distribution_report.csv',
            ],
        ];
    }

    public function handle()
    {
        $this->csv = new ReviewsDistributionCsv();
        $csvPath = $this->csv->resolveCsvPath((string) $this->option('csv'));

        if (!$csvPath) {
            $this->error('CSV не найден. Укажите --csv или скопируйте файл в storage/app/distribution_of_reviews.csv');
            return 1;
        }

        $rows = $this->csv->parseCsv($csvPath);
        if ($rows === []) {
            $this->error('В CSV нет валидных строк.');
            return 1;
        }

        [$resolvedRows, $resolveErrors] = $this->csv->resolveTargets($rows);
        [$mergedRows, $mergeWarnings] = $this->csv->mergeResolvedRowsByTarget($resolvedRows);

        $audit = new ReviewsDistributionAudit();
        $result = $audit->buildReport($mergedRows, $resolveErrors);
        $summaryRows = $audit->buildSummaryRows($result['summary']);

        $outputRelative = ltrim(str_replace(['\\', '..'], ['/', ''], (string) $this->option('output')), '/');
        $outputPath = storage_path('app/' . $outputRelative);
        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $written = $this->writeReportCsv($outputPath, $summaryRows, $result['rows']);

        $this->info('Эталон CSV: ' . $csvPath);
        $this->info('Отчёт: ' . $outputPath);
        $this->info('Строк в отчёте: ' . $written);
        $this->printConsoleSummary($result['summary'], $mergeWarnings);

        return ((int) $result['summary']['targets_problem'] === 0 && (int) $result['summary']['resolve_errors'] === 0)
            ? 0
            : 2;
    }

    /**
     * @param array<int, array<string, mixed>> $summaryRows
     * @param array<int, array<string, mixed>> $dataRows
     */
    private function writeReportCsv(string $path, array $summaryRows, array $dataRows): int
    {
        $fh = fopen($path, 'wb');
        if ($fh === false) {
            $this->error('Не удалось записать: ' . $path);
            return 0;
        }

        fwrite($fh, "\xEF\xBB\xBF");

        $header = [
            'Статус',
            'Проблемы',
            'page_type',
            'slug_or_id',
            'Название',
            'URL',
            'entity_type',
            'entity_id',
            'Строки_CSV',
            'Нужно_отзывов',
            'Привязано_в_БД',
            'Показ_на_сайте',
            'Источник_виджета',
            'ID_отзывов',
            'Теплоход_совпадает',
            'Уникальны_среди_круизов',
        ];

        fputcsv($fh, $header, ';');

        $all = array_merge($summaryRows, $dataRows);
        foreach ($all as $row) {
            fputcsv($fh, [
                $row['status'] ?? '',
                $row['issues'] ?? '',
                $row['page_type'] ?? '',
                $row['slug_or_id'] ?? '',
                $row['title'] ?? '',
                $row['url'] ?? '',
                $row['entity_type'] ?? '',
                $row['entity_id'] ?? '',
                $row['csv_lines'] ?? '',
                $row['expected_count'] ?? '',
                $row['binding_count'] ?? '',
                $row['widget_count'] ?? '',
                $row['widget_source'] ?? '',
                $row['review_ids'] ?? '',
                $row['ship_match'] ?? '',
                $row['unique_among_cruises'] ?? '',
            ], ';');
        }

        fclose($fh);

        return count($all);
    }

    /**
     * @param array<string, mixed> $summary
     * @param array<int, array<string, mixed>> $mergeWarnings
     */
    private function printConsoleSummary(array $summary, array $mergeWarnings): void
    {
        $this->info('--- Сводка ---');
        $this->line('Целей (уникальных страниц): ' . (int) $summary['targets_total']);
        $this->line('Статус OK: ' . (int) $summary['targets_ok']);
        $this->line('С проблемами: ' . (int) $summary['targets_problem']);
        $this->line('Ошибок резолва CSV: ' . (int) $summary['resolve_errors']);
        $this->line('Привязок в zen_reviews_bindings: ' . (int) $summary['bindings_total']);
        $this->line('Отзывов без ship_id (общий пул): ' . (int) $summary['general_pool_reviews']);
        $this->line('Теплоходов с отзывами в ship-пуле: ' . (int) $summary['ship_pool_ships']);

        if (!empty($summary['reviews_with_multiple_bindings'])) {
            $this->warn('Дубликаты review_id в bindings: ' . implode(', ', $summary['reviews_with_multiple_bindings']));
        }

        if ($mergeWarnings !== []) {
            $this->warn('В CSV несколько строк на одну страницу (в отчёте одна строка, count = max): ' . count($mergeWarnings));
        }

        if ((int) $summary['targets_problem'] === 0 && (int) $summary['resolve_errors'] === 0) {
            $this->info('Проверка пройдена — можно отдавать CSV клиенту.');
        } else {
            $this->warn('Есть строки со статусом не OK — см. колонки «Статус» и «Проблемы» в CSV.');
        }
    }
}
