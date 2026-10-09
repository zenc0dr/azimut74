<?php namespace Zen\Worker\Console\gama;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputOption;
use Exception;

/**
 * Обновляет только текст маршрута круизов Гамы (checkins.desc_1 и schedule_html в SQLite).
 * Цены, путевые листы и признак активности не меняет.
 */
class GamaRefreshRoute extends Command
{
    protected $name = 'worker:gama-refresh-route';
    protected $description = 'Обновить описания экскурсий во вкладке «Маршрут» у круизов Гамы';

    public function handle()
    {
        set_time_limit(0);

        try {
            if (!$this->option('no-download')) {
                $this->info('Скачивание архива Гамы...');
                (new GamaApiClient((int) $this->option('timeout')))->downloadGamaArchives();
            }

            $db = new GamaDatabase(false);
            $processor = new GamaDataProcessor($db, (int) $this->option('timeout'));
            $schedules = $processor->scheduleHtmlByCruiseId();
            $this->info('Расписаний в архиве: ' . count($schedules));

            $updated = 0;
            $withExcursions = 0;
            $missing = 0;

            foreach ($schedules as $cruiseId => $html) {
                $html = $this->limitHtml($html);
                if (strpos($html, 'Описание') !== false && preg_match('/<td>[^<]{20,}<\/td>\s*<\/tr>/u', $html)) {
                    $withExcursions++;
                }

                $db->updateScheduleHtml($cruiseId, $html);

                $affected = DB::table('mcmraak_rivercrs_checkins')
                    ->where('eds_code', 'gama')
                    ->where('eds_id', $cruiseId)
                    ->update(['desc_1' => $html]);

                if ($affected) {
                    $updated += $affected;
                } else {
                    $exists = DB::table('mcmraak_rivercrs_checkins')
                        ->where('eds_code', 'gama')
                        ->where('eds_id', $cruiseId)
                        ->exists();
                    if (!$exists) {
                        $missing++;
                    }
                }
            }

            $this->info("Заездов с текстом экскурсий: $withExcursions");
            $this->info("Обновлено записей MySQL: $updated");
            $this->info("Нет заезда в MySQL: $missing");
            $this->info('Цены и путевые листы не изменялись.');
        } catch (Exception $e) {
            $this->error($e->getMessage());
            return 1;
        }

        return 0;
    }

    protected function getOptions()
    {
        return [
            ['timeout', 't', InputOption::VALUE_OPTIONAL, 'Таймаут HTTP, секунды', 60],
            ['no-download', null, InputOption::VALUE_NONE, 'Не скачивать архив, взять уже распакованный storage/gama_arc'],
        ];
    }

    /**
     * desc_1 — TEXT, лимит 65535 байт. Длинный круиз с несколькими экскурсиями на стоянку его превышает.
     */
    private function limitHtml($html)
    {
        $limit = 60000;
        if (strlen($html) <= $limit) {
            return $html;
        }

        $original = $html;
        foreach ([400, 220, 120, 60] as $size) {
            $html = preg_replace('/(<td>)([^<]{' . $size . '})[^<]*/u', '$1$2…', $original);
            if (is_string($html) && strlen($html) <= $limit) {
                return $html;
            }
        }

        return is_string($html) ? $html : $original;
    }
}
