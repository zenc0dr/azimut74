<?php namespace Zen\Reviews\Console;

use Illuminate\Console\Command;
use Mcmraak\Rivercrs\Classes\ReviewsWidget;
use Symfony\Component\Console\Input\InputOption;
use Zen\Reviews\Models\Review;

class ExportCsv extends Command
{
    protected $name = 'reviews:export-csv';

    protected $description = 'Экспорт отзывов zen/reviews в CSV (UTF-8 BOM, разделитель |)';

    private const DELIMITER = '|';

    /** @var array<string, string> */
    private const COLUMNS = [
        'id' => 'ID',
        'name' => 'Имя',
        'email' => 'Email',
        'phone' => 'Телефон',
        'is_published' => 'Опубликован',
        'created_at' => 'Создано',
        'updated_at' => 'Обновлено',
        'ship_id' => 'ID теплохода',
        'ship_name' => 'Теплоход',
        'ship_short_name' => 'Краткое имя теплохода',
        'trip_date' => 'Дата поездки',
        'exp_rest' => 'Ранее отдыхали (код)',
        'exp_rest_label' => 'Ранее отдыхали',
        'rating_cabin' => 'Оценка каюты',
        'rating_food' => 'Оценка питания',
        'rating_service' => 'Оценка обслуживания',
        'rating_tours' => 'Оценка экскурсий',
        'rating_anim_on_board' => 'Оценка анимации на борту',
        'rating_ship' => 'Оценка теплохода',
        'rating_azimut' => 'Оценка работы Азимут',
        'rating_cruise' => 'Оценка отдыха в целом',
        'reviews_text' => 'Текст отзыва',
        'lead_id' => 'Lead ID',
        'binding_entity_type' => 'Привязка (тип)',
        'binding_entity_id' => 'Привязка (ID)',
        'photos_count' => 'Количество фото',
        'photo_urls' => 'URL фото',
        'admin_url' => 'Ссылка в админке',
    ];

    public function handle()
    {
        $outputPath = (string) ($this->option('output') ?: '-');
        $publishedOnly = (bool) $this->option('published-only');

        $query = Review::with(['photos', 'binding'])->orderBy('id');
        if ($publishedOnly) {
            $query->published();
        }

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->warn('Отзывов для экспорта не найдено.');

            return 1;
        }

        $handle = $outputPath === '-' ? STDOUT : fopen($outputPath, 'wb');
        if ($handle === false) {
            $this->error("Не удалось открыть файл: {$outputPath}");

            return 1;
        }

        fwrite($handle, "\xEF\xBB\xBF");
        fwrite($handle, $this->formatRow(array_values(self::COLUMNS)));

        $exported = 0;
        $query->chunk(200, function ($reviews) use ($handle, &$exported) {
            foreach ($reviews as $review) {
                fwrite($handle, $this->formatRow($this->rowFromReview($review)));
                $exported++;
            }
        });

        if ($outputPath !== '-') {
            fclose($handle);
            $this->info("Экспортировано {$exported} отзывов в {$outputPath}");
        } else {
            $this->line("Экспортировано отзывов: {$exported}", null, 'v');
        }

        return 0;
    }

    /**
     * @return list<string|int>
     */
    private function rowFromReview(Review $review): array
    {
        $form = ReviewsWidget::extractForm($review);
        $reviews = is_array($form['reviews'] ?? null) ? $form['reviews'] : [];
        $binding = $review->binding;
        $baseUrl = rtrim((string) env('APP_URL', ''), '/');
        $photoUrls = [];

        foreach ($review->photos as $photo) {
            $path = (string) $photo->getPath();
            $photoUrls[] = $path !== '' && $baseUrl !== ''
                ? $baseUrl . $path
                : $path;
        }

        $expRest = isset($form['exp_rest']) && is_numeric($form['exp_rest'])
            ? (int) $form['exp_rest']
            : null;

        $row = [
            'id' => (int) $review->id,
            'name' => (string) ($review->name ?? ''),
            'email' => (string) ($review->email ?? ''),
            'phone' => (string) ($review->phone ?? ''),
            'is_published' => $review->is_published ? '1' : '0',
            'created_at' => $review->created_at ? $review->created_at->format('Y-m-d H:i:s') : '',
            'updated_at' => $review->updated_at ? $review->updated_at->format('Y-m-d H:i:s') : '',
            'ship_id' => ReviewsWidget::normalizeShipId($form['ship_id'] ?? null) ?: '',
            'ship_name' => (string) ($form['ship_name'] ?? ''),
            'ship_short_name' => $review->ship_short_name,
            'trip_date' => (string) ($form['trip_date'] ?? ''),
            'exp_rest' => $expRest ?? '',
            'exp_rest_label' => $this->expRestLabel($expRest),
            'rating_cabin' => $this->ratingValue($reviews, 'cabin'),
            'rating_food' => $this->ratingValue($reviews, 'food'),
            'rating_service' => $this->ratingValue($reviews, 'service'),
            'rating_tours' => $this->ratingValue($reviews, 'tours'),
            'rating_anim_on_board' => $this->ratingValue($reviews, 'anim_on_board'),
            'rating_ship' => $this->ratingValue($reviews, 'ship'),
            'rating_azimut' => $this->ratingValue($reviews, 'azimut'),
            'rating_cruise' => $this->ratingValue($reviews, 'cruise'),
            'reviews_text' => (string) ($form['reviews_text'] ?? ''),
            'lead_id' => (string) ($form['lead_id'] ?? ''),
            'binding_entity_type' => $binding ? (string) $binding->entity_type : '',
            'binding_entity_id' => $binding ? (int) $binding->entity_id : '',
            'photos_count' => count($photoUrls),
            'photo_urls' => implode('; ', $photoUrls),
            'admin_url' => $baseUrl !== ''
                ? $baseUrl . '/console/zen/reviews/reviews/update/' . (int) $review->id
                : '/console/zen/reviews/reviews/update/' . (int) $review->id,
        ];

        $ordered = [];
        foreach (array_keys(self::COLUMNS) as $key) {
            $ordered[] = $row[$key];
        }

        return $ordered;
    }

    private function expRestLabel(?int $value): string
    {
        switch ($value) {
            case 1:
                return 'Первый раз';
            case 2:
                return 'Второй раз';
            case 3:
                return 'Три и более';
            default:
                return '';
        }
    }

    /**
     * @param array<string, mixed> $reviews
     */
    private function ratingValue(array $reviews, string $key): string
    {
        if (!array_key_exists($key, $reviews)) {
            return '';
        }

        $value = $reviews[$key];
        if ($value === null || $value === '') {
            return '';
        }

        return is_numeric($value) ? (string) (int) round((float) $value) : '';
    }

    /**
     * @param list<string|int> $fields
     */
    private function formatRow(array $fields): string
    {
        $escaped = array_map(function ($value) {
            return $this->csvEscape((string) $value);
        }, $fields);

        return implode(self::DELIMITER, $escaped) . "\n";
    }

    private function csvEscape(string $value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        if (
            strpos($value, self::DELIMITER) !== false
            || strpos($value, '"') !== false
            || strpos($value, "\n") !== false
        ) {
            return '"' . str_replace('"', '""', $value) . '"';
        }

        return $value;
    }

    protected function getOptions()
    {
        return [
            ['output', 'o', InputOption::VALUE_OPTIONAL, 'Путь к файлу (- для stdout)', '-'],
            ['published-only', null, InputOption::VALUE_NONE, 'Только опубликованные отзывы'],
        ];
    }
}
