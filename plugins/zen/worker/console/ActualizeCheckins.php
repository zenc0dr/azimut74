<?php namespace Zen\Worker\Console;

use Illuminate\Console\Command;
use Schema;
use Symfony\Component\Console\Input\InputOption;
use Zen\Worker\Classes\CheckinActualizer;
use Zen\Worker\Classes\WorkerNotifier;

class ActualizeCheckins extends Command
{
    protected $name = 'worker:actualize-checkins';
    protected $description = 'Актуализация заездов: истечение по дате и точечная проверка отозванных';

    public function handle()
    {
        if (!Schema::hasTable('mcmraak_rivercrs_checkin_probes')) {
            $this->error('Таблица mcmraak_rivercrs_checkin_probes не создана. Запустите php artisan october:up');
            return 1;
        }

        $dryRun = (bool) $this->option('dry-run');
        $expire = (bool) $this->option('expire');
        $probe = (bool) $this->option('probe');
        $withdraw = (bool) $this->option('withdraw');
        $once = (bool) $this->option('once');
        $checkinId = (int) $this->option('checkin-id');

        if ($once) {
            $expire = true;
            $probe = true;
        }
        if (!$expire && !$probe && !$withdraw) {
            $expire = true;
            $probe = true;
        }

        $onlyId = $checkinId > 0 ? $checkinId : null;
        $actualizer = new CheckinActualizer($this, $dryRun);
        $runStartedAt = $actualizer->resolveRunStartedAt($this->option('run-started'));

        $this->info('worker:actualize-checkins' . ($dryRun ? ' (dry-run)' : ''));
        $this->info('run_started_at=' . $runStartedAt);

        $expired = ['count' => 0];
        $probed = ['ok' => 0, 'fail' => 0, 'skipped' => 0];
        $withdrawn = ['count' => 0];

        if ($expire) {
            $this->info('— expire: DATE(date) < сегодня');
            $expired = $actualizer->expire($onlyId);
            $this->info('истекло: ' . $expired['count']);
        }
        if ($probe) {
            $this->info('— probe: точечный повтор источников');
            $probed = $actualizer->probe($runStartedAt, $onlyId);
            $this->info(sprintf(
                'probe ok=%d fail=%d skipped=%d',
                $probed['ok'],
                $probed['fail'],
                $probed['skipped']
            ));
        }
        if ($withdraw) {
            $this->info('— withdraw: 3 неуспешные попытки');
            $withdrawn = $actualizer->withdraw($onlyId);
            $this->info('отозвано: ' . $withdrawn['count']);
        }

        $msg = sprintf(
            'Актуализация заездов%s: истекло %d, probe ok %d / fail %d, отозвано %d',
            $dryRun ? ' [dry-run]' : '',
            $expired['count'],
            $probed['ok'] ?? 0,
            $probed['fail'] ?? 0,
            $withdrawn['count']
        );
        $this->info($msg);

        $deleted = (int) ($expired['count'] ?? 0) + (int) ($withdrawn['count'] ?? 0);
        if ($deleted > 0) {
            $this->info('— сброс кеша фронта (Cabox rivercrs, CMS, версия поиска)');
            $cache = $actualizer->refreshFrontendCaches();
            if (!$dryRun && !empty($cache['search_version'])) {
                $msg .= sprintf(', поиск v%d', $cache['search_version']);
            }
        }

        if (!$this->option('no-telegram')) {
            WorkerNotifier::notify($msg);
        }
        return 0;
    }

    protected function getOptions()
    {
        return [
            ['expire', null, InputOption::VALUE_NONE, 'Удалить заезды с прошедшей датой ОТ'],
            ['probe', null, InputOption::VALUE_NONE, 'Одна попытка точечного парсинга пропавших заездов'],
            ['withdraw', null, InputOption::VALUE_NONE, 'Удалить заезды после 3 неуспешных попыток'],
            ['once', null, InputOption::VALUE_NONE, 'expire + probe (сразу после парсеров)'],
            ['dry-run', null, InputOption::VALUE_NONE, 'Только список, без удаления и sync'],
            ['no-telegram', null, InputOption::VALUE_NONE, 'Не слать уведомление'],
            ['run-started', null, InputOption::VALUE_OPTIONAL, 'Якорь прогона (Y-m-d H:i:s). По умолчанию storage/parsers_run_started_at', null],
            ['checkin-id', null, InputOption::VALUE_OPTIONAL, 'Только один checkin_id', null],
        ];
    }
}
