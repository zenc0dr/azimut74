<?php namespace Zen\Worker\Pools;

use Zen\Worker\Classes\ProcessLog;
use Zen\Worker\Models\ErrorLog;
use Zen\Worker\Models\Job;

class InfoflotService extends RiverCrs
{
    function clean()
    {
        ProcessLog::add('Очистка кэша потока');
        $cache = $this->stream->cache;
        $this->stream->cache->handleItems(function ($item) use ($cache) {
            $key = $item['key'];
            if(strpos($key, 'infoflot.') === 0) $cache->del($key);
        });

        $jobs = Job::whereNotNull('error')->get();

        if(!$jobs->count()) return;

        ProcessLog::add("Перенос незавершённых задач ({$jobs->count()} шт.) в журнал ошибок");

        foreach ($jobs as $job) {
            $bad_job = new ErrorLog;
            $bad_job->fill($job->makeHidden('id')->toArray());
            $bad_job->save();
            $job->delete();
        }

        ErrorLog::rotate();
        $this->removeNotActualCheckins('germes');
    }
}
