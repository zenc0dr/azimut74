<?php namespace Zen\Worker\Console;

use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;
use Mcmraak\Rivercrs\Classes\CacheSettings;
use Mcmraak\Rivercrs\Models\Checkins as Checkin;
use Symfony\Component\Console\Input\InputOption;
use Zen\Cabox\Classes\Cabox;
use Zen\Worker\Classes\CheckinActualizer;

class PurgeOnedayCheckins extends Command
{
    protected $name = 'worker:purge-oneday-checkins';
    protected $description = 'Отчёт и удаление однодневных заездов. Без --apply ничего не удаляет.';

    public function handle()
    {
        $apply = (bool) $this->option('apply');
        $minDays = max(2, (int) CacheSettings::get('days_diff'));

        $this->info('worker:purge-oneday-checkins' . ($apply ? ' (--apply)' : ' (dry-run)'));
        $this->info('days_diff=' . CacheSettings::get('days_diff') . ', порог удаления: days=1 или один календарный день');
        $this->info('порог импорта (для справки): days < ' . $minDays);

        $rows = DB::table('mcmraak_rivercrs_checkins')
            ->select('id', 'eds_code', 'eds_id', 'days', 'date', 'dateb', 'active')
            ->where(function ($query) {
                $query->where('days', 1)
                    ->orWhereRaw('DATE(`date`) = DATE(`dateb`)');
            })
            ->orderBy('eds_code')
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            $this->info('Однодневных заездов нет.');
            return 0;
        }

        $ids = $rows->pluck('id')->map(function ($id) {
            return (int) $id;
        })->all();

        $bookedIds = DB::table('mcmraak_rivercrs_booking')
            ->whereIn('checkin_id', $ids)
            ->pluck('checkin_id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->flip();

        $bySource = [];
        $toDelete = [];
        $kept = [];

        foreach ($rows as $row) {
            $id = (int) $row->id;
            $calendarDays = $this->inclusiveDays($row->date, $row->dateb);
            $booked = isset($bookedIds[$id]);
            $line = sprintf(
                '%s id=%d eds=%s:%s days=%s calendar=%d %s — %s active=%s',
                $booked ? 'KEEP' : ($apply ? 'DEL' : 'WOULD'),
                $id,
                $row->eds_code,
                $row->eds_id,
                $row->days,
                $calendarDays,
                $row->date . ' .. ' . $row->dateb,
                $booked ? 'есть бронь' : 'однодневный',
                $row->active
            );
            $this->line($line);

            $bySource[$row->eds_code] = ($bySource[$row->eds_code] ?? 0) + 1;
            if ($booked) {
                $kept[] = $id;
            } else {
                $toDelete[] = $id;
            }
        }

        foreach ($bySource as $code => $count) {
            $this->info("$code: $count");
        }
        $this->info('к удалению: ' . count($toDelete) . ', с бронью (не трогаем): ' . count($kept));

        if (!$apply) {
            $this->warn('dry-run: записи не изменены. Для удаления добавьте --apply.');
            return 0;
        }

        $deleted = 0;
        foreach ($toDelete as $id) {
            DB::table('mcmraak_rivercrs_checkins_memory')->where('checkin_id', $id)->delete();
            $cabox = new Cabox('rivercrs');
            $cabox->del('rcrs:' . $id);
            $cabox->del('exist_array:' . $id);
            $cabox->del('exist:' . $id);

            $checkin = Checkin::find($id);
            if (!$checkin) {
                $this->warn("id=$id уже отсутствует");
                continue;
            }
            $checkin->delete();
            $deleted++;
        }

        $this->info('удалено: ' . $deleted);

        if ($deleted > 0) {
            $actualizer = new CheckinActualizer($this, false);
            $actualizer->refreshFrontendCaches();
        }

        $left = DB::table('mcmraak_rivercrs_checkins')
            ->where(function ($query) {
                $query->where('days', 1)
                    ->orWhereRaw('DATE(`date`) = DATE(`dateb`)');
            })
            ->count();
        $this->info('осталось однодневных (включая брони): ' . $left);

        return 0;
    }

    /**
     * Та же формула, что Checkins::beforeSave.
     */
    private function inclusiveDays($dateStart, $dateEnd): int
    {
        $start = Carbon::parse(date('Y-m-d', strtotime($dateStart)));
        $end = Carbon::parse(date('Y-m-d', strtotime($dateEnd)));
        return $end->diffInDays($start) + 1;
    }

    protected function getOptions()
    {
        return [
            ['apply', null, InputOption::VALUE_NONE, 'Удалить однодневные заезды. Без флага только отчёт.'],
        ];
    }
}
